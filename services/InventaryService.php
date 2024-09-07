<?php

namespace app\services;
use app\controllers\MovimientoAlmacenController;
use app\controllers\MovimientoAlmacenDetalleController;
use app\controllers\MovimientoAlmacenDetallePresController;
use app\models\ArqueoInventario;
use app\models\DetalleArqueoInventario;
use app\models\DetallePresArqueoInventario;
use app\models\Inventario;
use app\models\InventarioPres;
use Exception;
use Yii;

class InventaryService {
    public function createInventary($params) {
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            $inventaryAudit = $this->createInventaryAudit($params['auditInventary']);
            $entryAdjustmentMovement = $this->createWarehouseMovement($inventaryAudit, $params['entryAdjustmentMovement'], 4);
            $exitAdjustmentMovement = $this->createWarehouseMovement($inventaryAudit, $params['exitAdjustmentMovement'], 11);

            foreach ($params['inventariesList'] as $inventaryItem) {
                $this->processInventaryItem($inventaryItem, $inventaryAudit, $entryAdjustmentMovement, $exitAdjustmentMovement, $params['type']);
            }

            $transaction->commit();
            return [
                'success' => true,
                'message' => 'Inventario registrado'
            ];
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::$app->getResponse()->setStatusCode(500, 'Data Validation Failed.');
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function createInventaryAudit($auditInventary) {
        $inventaryAudit = new ArqueoInventario();
        $inventaryAudit->load($auditInventary, '');
        date_default_timezone_set('America/La_Paz');
        $inventaryAudit->fecha = date('Y-m-d H:i:s');

        if (!$inventaryAudit->save()) {
            throw new \Exception('No se pudo registrar el inventario');
        }
        return $inventaryAudit;
    }

    private function createWarehouseMovement($inventaryAudit, $adjustmentMovement, $conceptId) {
        if (count($adjustmentMovement) > 0) {
            $warehouseMovement = new MovimientoAlmacenController("", '');
            return $warehouseMovement->create(array_merge($inventaryAudit->toArray(), [
                'concepto_mov_almacen_id' => $conceptId
            ]));
        }
        return null;
    }

    private function processInventaryItem($inventaryItem, $inventaryAudit, $entryAdjustmentMovement, $exitAdjustmentMovement, $type) {
        $inventaryAuditDetail = $this->inventaryAuditFactory($type);
        $inventaryAuditDetail->load($inventaryItem, '');
        $inventaryAuditDetail->arqueo_inventario_id = $inventaryAudit->id;
        $inventaryAuditDetail->teorico_almacen = $inventaryItem['cantidad'];

        if (!$inventaryAuditDetail->save()) {
            throw new \Exception('Error al guardar el detalle del inventario');
        }

        $inventary = $this->inventaryFactory($type, $inventaryItem['id']);
        $inventary->cantidad = $inventaryItem['fisico_almacen'];

        if (!$inventary->save()) {
            throw new \Exception('Error al actualizar el inventario');
        }

        $diff = $inventaryItem['cantidad'] - $inventaryItem['fisico_almacen'];
        if ($diff !== 0) {
            $movimientoAlmacenId = $diff > 0 ? $exitAdjustmentMovement->id : $entryAdjustmentMovement->id;
            $this->createWarehouseMovementDetail($inventaryItem, $movimientoAlmacenId, abs($diff), $type);
        }
    }

    private function createWarehouseMovementDetail($inventaryItem, $movimientoAlmacenId, $cantidad, $type) {
        $detailWarehouseMovement = $this->warehouseMovementFactory($type);
        $detailWarehouseMovement->create(array_merge($inventaryItem, [
            'movimiento_almacen_id' => $movimientoAlmacenId,
            'cantidad' => $cantidad
        ]));
    }

    private function inventaryAuditFactory($type){
        switch($type){
            case 'inventario':
                return new DetalleArqueoInventario();
            case 'inventario-pres':
                return new DetallePresArqueoInventario();
            default:
                return null;
        }
    }

    private function WarehouseMovementFactory($type){
        switch($type){
            case 'inventario':
                return new MovimientoAlmacenDetalleController('', '');
            case 'inventario-pres':
                return new MovimientoAlmacenDetallePresController('', '');
            default:
                return null;
        }
    }

    private function inventaryFactory ($type, $id){
        switch($type){
            case 'inventario':
                return Inventario::findOne($id); ;
            case 'inventario-pres':
                return InventarioPres::findOne($id);
            default:
                return null;
        }
    }
}