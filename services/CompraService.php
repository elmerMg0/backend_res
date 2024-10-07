<?php

namespace app\services;

use app\controllers\InsumoController;
use app\controllers\MovimientoAlmacenController;
use app\controllers\MovimientoAlmacenDetalleController;
use app\controllers\MovimientoAlmacenDetallePresController;
use app\controllers\PresentacionController;
use app\models\Almacen;
use app\models\Compra;
use app\models\DetalleCompra;
use app\models\Inventario;
use app\models\InventarioPres;
use app\models\Producto;
use app\models\Receta;
use Exception;
use Yii;

class CompraService
{
    public function createCompra($params)
    {
        $purchase = new Compra();
        $purchase->load($params['purchaseInformation'], '');
        $purchase->importe = $params['importe'];
        $purchase->usuario_id = $params['user'];

        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($purchase->save()) {
                foreach ($params['presentationsSelected'] as $presentation) {
                    $this->createDetalleCompra($presentation, $purchase->id);
                    $this->handleInventarioUpdate($presentation, $params['user']);
                    $this->handleProductUpdate($params['presentationsSelected']);
                }
                $transaction->commit();
                return [
                    'success' => true,
                    'message' => 'La compra se ha registrado con éxito'
                ];
            } else {
                return $purchase->errors;
                //throw new Exception('Error al registrar la compra');
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function handleProductUpdate($items){
        $uniqueArray = array_column($items, 'insumo_id');

        $uniqueArray = Receta::find()->where(['insumo_id' => $uniqueArray])->asArray()->all();

        $uniqueArray = array_reduce($uniqueArray, function ($carry, $item) {
            $key = $item['producto_id'];
            if (!isset($carry[$key])) {
                $carry[$key] = $item;
            }
            return $carry;
        });

        if(!$uniqueArray)return;

        foreach ($uniqueArray as $item) {
            $product = Producto::findOne($item['producto_id']);
            $saleCost = Receta::find() 
                                ->select(['receta.*', 'insumo.costo_promedio'])
                                ->innerJoin('insumo', 'insumo.id = insumo_id')    
                                ->where(['producto_id' => $item['producto_id']])
                                ->sum('insumo.ultimo_costo_c_merma * receta.cantidad'); 
            if($saleCost > 0 && $product -> costo_compra != $saleCost){
                $product->costo_compra = $saleCost;
                if(!$product->save()){
                    throw new Exception('Error al registrar la compra');
                }
            }   
        }
    }

    private function createDetalleCompra($presentation, $compraId)
    {
        $detail = new DetalleCompra();
        $detail->load($presentation, '');
        $detail->presentacion_id = $presentation['id'];
        $detail->compra_id = $compraId;
        $detail->costo_unitario = $presentation['ultimo_costo'];
        if (!$detail->save()) {
            $errors = $detail->errors;
            $errorMessage = "Errores al guardar: " . json_encode($errors);
            Yii::$app->getResponse()->setStatusCode(500, 'Data Validation Failed.');
            throw new \Exception($errorMessage);
        }
    }

    private function handleInventarioUpdate($presentation, $user)
    {
        $purchaseEntryMovement = new MovimientoAlmacenController('', '');
        $idConcep = 5; // Entrada por compra
        $model = $purchaseEntryMovement->create(array_merge($presentation, [
            'concepto_mov_almacen_id' => $idConcep,
            'usuario_id' => $user
        ]));

        /* Update presentacion cost */
        $presentationController = new PresentacionController('', '');
        $presentationModal = $presentationController->updatePresentation($presentation['id'], [
            'ultimo_costo' => $presentation['ultimo_costo'],
        ]);
        $presentationModal -> save();

        /* update insumo cost */
        $supplies = new InsumoController('', '');
        $suppliesModel = $supplies->updateSuppliesCost($presentation['insumo_id'], [
            'ultimo_costo' => $presentation['ultimo_costo'] / $presentation['rendimiento'],
        ], $presentation['rendimiento']);
        $suppliesModel -> save();

        $warehouse = Almacen::findOne($presentation['almacen_id']);
        if ($warehouse->tipo == 'Centro de consumo') {
            $this->updateInventario($presentation, $model->id);
        } else {
            $this->updateInventarioPres($presentation, $model->id);
        }
    }

    private function updateInventario($presentation, $movimientoId)
    {
        $inventary = Inventario::find()
            ->where(['insumo_id' => $presentation['insumo_id'], 'almacen_id' => $presentation['almacen_id']])
            ->one();
        if ($inventary) {
            $inventary->cantidad += $presentation['cantidad'] * $presentation['rendimiento'];
        } else {
            $inventary = new Inventario();
            $inventary->load($presentation, '');
        }
        $inventary->save();

        $purchaseEntryMovementDetail = new MovimientoAlmacenDetalleController('', '');
        $purchaseEntryMovementDetail->create(array_merge($presentation, [
            'movimiento_almacen_id' => $movimientoId,
            'costo_unitario' => $presentation['ultimo_costo'],
        ]));
    }

    private function updateInventarioPres($presentation, $movimientoId)
    {
        $inventaryPres = InventarioPres::find()
            ->where(['presentacion_id' => $presentation['id'], 'almacen_id' => $presentation['almacen_id']])
            ->one();
        if ($inventaryPres) {
            $inventaryPres->cantidad += $presentation['cantidad'];
        } else {
            $inventaryPres = new InventarioPres();
            $inventaryPres->load($presentation, '');
        }
        $inventaryPres->save();


        $purchaseEntryMovementDetail = new MovimientoAlmacenDetallePresController('', '');
        $purchaseEntryMovementDetail->create(array_merge($presentation, [
            'movimiento_almacen_id' => $movimientoId,
            'cantidad' => $presentation['cantidad'],
            'costo_unitario' => $presentation['ultimo_costo'],
        ]));
    }
}
