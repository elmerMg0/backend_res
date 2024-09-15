<?php
namespace app\services;

use app\controllers\InsumoController;
use Yii;
use app\models\Periodo;
use app\models\Venta;
use app\models\Inventario;
use app\controllers\MovimientoAlmacenController;
use app\controllers\MovimientoAlmacenDetalleController;
use app\models\MovimientoAlmacen;
use yii\db\Expression;

class PeriodoService
{
    public function closePeriod($idPeriod, $idUser, $totalCierreCaja)
    {
        $period = Periodo::findOne($idPeriod);
        date_default_timezone_set('America/La_Paz');
        $period->fecha_fin = date('Y-m-d H:i:s');
        $period->estado = false;

        $totalSale = $this->calculateTotalSales($period, $idUser);
        $period->total_ventas = $totalSale;
        $period->total_cierre_caja = $totalCierreCaja;

        $saleDetails = $this->fetchSaleDetails($period, $idUser);
        $suppliesId = $this->processSaleDetails($saleDetails, $idUser);
        $this -> validateStock($suppliesId);

        if ($period->save()) {
            return [
                'success' => true,
                'message' => 'Periodo cerrado con exito!',
                'period' => $period
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Existen parametros incorrectos',
                'errors' => $period->errors
            ];
        }
    }

    private function validateStock($suppliesId){
        for($index = 0; $index < count($suppliesId); $index++){
            $model = new InsumoController('', '');
            $model -> validateStockMin($suppliesId[$index]);
        }
    }

    private function calculateTotalSales($period, $idUser)
    {
        return Venta::find()
            ->where(['>=', 'fecha', $period->fecha_inicio])
            ->andWhere(['usuario_id' => $idUser, 'estado' => 'pagado'])
            ->sum('cantidad_total');
    }

    private function fetchSaleDetails($period, $idUser)
    {
        return Venta::find()
            ->select([ new Expression('detalle_venta.cantidad * receta.cantidad AS cantidad'), 'receta.insumo_id', 'asignacion_area_almacen.almacen_id', 'insumo.ultimo_costo as costo_unitario'])
            ->where(['>=', 'fecha', $period->fecha_inicio])
            ->andWhere(['usuario_id' => $idUser, 'venta.estado' => 'pagado'])
            ->andWhere(['<>', 'detalle_venta.estado', 'cancelado'])
           /*  ->innerJoin('mesa', 'mesa.id = venta.mesa_id') */
            ->innerJoin('area_venta', 'area_venta.id = venta.area_venta_id')
            ->innerJoin('detalle_venta', 'detalle_venta.venta_id = venta.id')
            ->innerJoin('producto', 'detalle_venta.producto_id = producto.id')
            ->innerJoin('receta', 'receta.producto_id = producto.id')
            ->innerJoin('insumo', 'insumo.id = receta.insumo_id')
            ->innerJoin('asignacion_area_almacen', 'asignacion_area_almacen.receta_id = receta.id')
            ->andWhere(['asignacion_area_almacen.area_venta_id' => new Expression('area_venta.id')])
            ->asArray()
            ->all();
    }

    private function processSaleDetails($saleDetails, $idUser)
    {
        $warehouseMovements = [];
        $transaction = Yii::$app->db->beginTransaction();
        $suppliesId = [];
        try {
            foreach ($saleDetails as $value) {
                $this->updateInventory($value);
                $model = $this->createWarehouseMovement($value, $idUser, $warehouseMovements);
                $this->createWarehouseMovementDetail($value, $model);
                if (!in_array($value['insumo_id'], $suppliesId)) {
                    $suppliesId[] = $value['insumo_id'];
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

        return $suppliesId;
    }

    private function updateInventory($value)
    {
        $inventario = Inventario::findOne(['insumo_id' => $value['insumo_id'], 'almacen_id' => $value['almacen_id']]);
        if ($inventario) {
            $inventario->cantidad -= $value['cantidad'];
            $inventario->save();
        }
    }

    private function createWarehouseMovement($value, $idUser, &$warehouseMovements)
    {
        if(isset($warehouseMovements[$value['almacen_id']])){
            $model = MovimientoAlmacen::findOne($warehouseMovements[$value['almacen_id']]);
            return $model;
        }

        $purchaseEntryMovement = new MovimientoAlmacenController('', '');
        $idConcep = 16; // Salida por venta
        $model = $purchaseEntryMovement->create(array_merge($value, [
            'concepto_mov_almacen_id' => $idConcep,
            'usuario_id' => $idUser
        ]));

        if (!isset($warehouseMovements[$value['almacen_id']])) {
            $warehouseMovements[$value['almacen_id']] = $model->id;
        }

        return $model;
    }

    private function createWarehouseMovementDetail($value, $model)
    {
        $purchaseEntryMovementDetail = new MovimientoAlmacenDetalleController('', '');
        $purchaseEntryMovementDetail->create(array_merge($value, [
            'movimiento_almacen_id' => $model->id,
        ]));
    }
}