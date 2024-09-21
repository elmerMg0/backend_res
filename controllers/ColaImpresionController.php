<?php

namespace app\controllers;

use app\models\AsignacionImpresora;
use app\models\ColaImpresion;
use app\models\ConfiguracionImpresora;
use app\models\DetalleVenta;
use app\models\DetalleVentaImprimir;
use app\models\Empresa;
use app\models\Impresora;
use app\models\Venta;
use Yii;

class ColaImpresionController extends \yii\web\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors["verbs"] = [
            "class" => \yii\filters\VerbFilter::class,
            "actions" => [
                'index' => ['get'],
                'create' => ['post'],
                'update' => ['put', 'post'],
                'delete' => ['delete'],
                'get-customer' => ['get'],
                'customers' => ['get'],
            ]
        ];
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['options']
        ];
        return $behaviors;
    }

    public function beforeAction($action)
    {
        if (Yii::$app->getRequest()->getMethod() === 'OPTIONS') {
            Yii::$app->getResponse()->getHeaders()->set('Allow', 'POST GET PUT');
            Yii::$app->end();
        }

        $this->enableCsrfValidation = false;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }
    /* Retorna las ventas, que no esten finalizadas, con los productos mas nuevos.   */
    public function actionIndex()
    {
        $printSpooler = [];
        $printSpoolerGlobal = ColaImpresion::find()->where(['estado' => false])->all();

        for ($i = 0; $i < count($printSpoolerGlobal); $i++) {
            $print = $printSpoolerGlobal[$i];
            $sale = Venta::find()
                ->select(['venta.*', 'usuario.username', 'mesa.nombre as mesa', 'cliente.nombre as cliente'])
                ->innerJoin('usuario', 'usuario.id = venta.usuario_id')
                ->innerJoin('mesa', 'mesa.id = venta.mesa_id')
                ->innerJoin('cliente', 'cliente.id = venta.cliente_id')
                ->where(['venta.id' => $print->venta_id])
                ->asArray()
                ->one();
            /* Si es cocina, solo agregamos los nuevos pedidios, si es salon todos */
            $orderDetail = [];
            $send = true;
            if ($print->area == 'cocina') {
                $orderDetail = DetalleVentaImprimir::find()
                    ->select(['detalle_venta_imprimir.cantidad', 'producto.*', 'detalle_venta_imprimir.id'])
                    ->where(['cola_impresion_id' => $print->id, 'producto.tipo' => 'comida'])
                    ->innerJoin('producto', 'producto.id=detalle_venta_imprimir.producto_id')
                    ->asArray()
                    ->all();
                if (!$orderDetail) {
                    $send = false;
                }
            } else {
                $orderDetail = DetalleVenta::find()
                    ->select(['detalle_venta.cantidad', 'producto.*'])
                    ->where(['venta_id' => $print->venta_id])
                    ->innerJoin('producto', 'producto.id=detalle_venta.producto_id')
                    ->asArray()
                    ->all();
            }
            $printer = AsignacionImpresora::find()->where(['area_impresion_id' => $print->area_impresion_id])->one();
            $infoSale = [
                'nro_pedido' => $sale["numero_pedido"],
                'nro_mesa' => $sale["mesa"],
                'cliente' => $sale["cliente"],
                'orderDetail' => $orderDetail,
                'note' => $sale['nota'],
                'printerName' => $printer->nombre,
                'place' => $printer->lugar,
                'username' => $sale["username"],
                'cantidad_total' => $sale['cantidad_total']
            ];
            if ($send) {
                $print->estado = true;
                $print->save();
                $printSpooler[] = $infoSale;
            }
        }

        $response = [
            'success' => true,
            'message' => 'Cola de impresions',
            'printSpooler' => $printSpooler

        ];
        return $response;
    }


    public function actionIndexImproved()
    {
        $printSpooler = [];
        $printSpoolerGlobal = ColaImpresion::find()->where(['estado' => false])->all();

        for ($i = 0; $i < count($printSpoolerGlobal); $i++) {
            $print = $printSpoolerGlobal[$i];

            $printer = AsignacionImpresora::find()
                ->select(['asignacion_impresora.*', 'area_impresion.nombre as area_impresion'])
                ->innerJoin('area_impresion', 'area_impresion.id = asignacion_impresora.area_impresion_id')
                ->where(['area_impresion_id' => $print->area_impresion_id])
                ->asArray()
                ->one();

            //filtramos los productos compuestos que tiene valor 0 cuando se imprime en CAJA.    
            $price = $printer && $printer['area_impresion'] === 'Caja' ? 0 : null;

            $query = Venta::find()
                ->select(['venta.*', 'usuario.username', 'area_venta.nombre as area_venta', 'cliente.nombre as cliente', 'venta_descuento.valor as descuento'])
                ->innerJoin('venta_area_impresion', 'venta.id = venta_area_impresion.venta_id')
                ->innerJoin('usuario', 'usuario.id = venta.usuario_id')
                ->innerJoin('cliente', 'cliente.id = venta.cliente_id')
                ->innerJoin('area_venta', 'area_venta.id = venta.area_venta_id')
                ->leftJoin('venta_descuento', 'venta.id = venta_descuento.venta_id')
                ->where(['venta.id' => $print->venta_id])
                ->with(['detalleVentas' => function ($query) use ($print, $price) {
                    $query
                        ->select(['detalle_venta.*', 'producto.nombre'])
                        ->innerJoin('producto', 'producto.id = detalle_venta.producto_id')
                        ->where(['detalle_venta.impreso' => false])
                        ->andWhere([
                            'OR',
                            ['producto.area_impresion_id' => $print->area_impresion_id], // Incluye productos con el área de impresión actual
                            [
                                'AND',
                                ['detalle_venta.detalle_venta_id' => null],   // Padres sin área de impresión
                                [
                                    'EXISTS',
                                    (new \yii\db\Query())
                                        ->from('detalle_venta dv2')
                                        ->innerJoin('producto p2', 'p2.id = dv2.producto_id')
                                        ->where('dv2.detalle_venta_id = detalle_venta.id')
                                        ->andWhere(['p2.area_impresion_id' => $print->area_impresion_id])  // Verifica que tienen al menos un hijo con área de impresión
                                ]
                            ]
                        ])
                        ->andFilterWhere(['>', 'detalle_venta.precio_venta', $price])
                        ->orderBy([
                            new \yii\db\Expression('COALESCE(detalle_venta.detalle_venta_id, detalle_venta.id) DESC'), // Ordena por el detalle principal o su propio ID si es un producto principal
                            new \yii\db\Expression('detalle_venta.detalle_venta_id IS NOT NULL ASC'),   // Asegura que los productos principales se muestren antes que sus modificadores
                            'detalle_venta.id' => SORT_DESC, // Ordena por ID para mantener el orden de inserción
                        ])
                        ->asArray();
                }])
                ->orderBy(['venta.id' => SORT_DESC])
                ->asArray()
                ->one();

            if (isset($query['detalleVentas']) && count($query['detalleVentas']) > 0) {
                $isNew = DetalleVenta::find()->where(['AND', ['venta_id' => $print->venta_id], ['impreso' => false]])->exists();

                $infoSale = [
                    'nro_pedido' => $query["numero_pedido"],
                    'nro_mesa' => $query["nro_mesa"],
                    'cantidad_total' => $query['cantidad_total'],
                    'cliente' => $query["cliente"],
                    'orderDetails' => $query["detalleVentas"],
                    'printerName' => $printer['printer_name'],
                    'place' => $printer['area_impresion'] === 'Caja' ? 'Caja' : 'Servicio',
                    'username' => $query["username"],
                    'note' => $query['nota'],
                    'is_new' => $isNew ? true : false,
                    'descuento' => $query['descuento'] ? $query['descuento'] : 0,
                    'tipo_area' => $query['mesa_id'] ? 'Comedor' : 'Rapido',
                    'area_venta' => $query['area_venta']

                ];
                $printSpooler[] = $infoSale;

                //Si todos tienen el campo impreso false entonces es NUEVA ORDEN, sino AGREGADO
                $detalleVentaIds = array_column($query['detalleVentas'], 'id');

                DetalleVenta::updateAll(['impreso' => true], ['id' => $detalleVentaIds]);
            }
            $model = ColaImpresion::findOne($print->id);
            $model->estado = true;
            $model->save();
        }

        $configuration = ConfiguracionImpresora::find()->one();
        $company = Empresa::find()->one();
        $response = [
            'success' => true,
            'message' => 'Cola de impresions',
            'printSpooler' => [
                'printSpooler' => $printSpooler,
                'configuration' => [...$configuration, 'bussines_name' => $company->nombre, 'phone' => $company->telefono]
            ]
        ];
        return $response;
    }
    public function actionCreate()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $printSpooler = new ColaImpresion();
        $printSpooler->load($params, '');

        if ($printSpooler->save()) {
            $response = [
                'success' => true,
                'message' => 'Se agrego correctamente',
                'printSpooler' => $printSpooler
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Ocurrio un error',
                'errors' => $printSpooler->errors
            ];
        }
        return $response;
    }
}
