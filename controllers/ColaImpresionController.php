<?php

namespace app\controllers;

use app\models\ColaImpresion;
use app\models\DetalleVenta;
use app\models\Impresora;
use app\models\Venta;
use Yii;

class ColaImpresionController extends \yii\web\Controller
{
    public function behaviors(){
        $behaviors = parent::behaviors();
        $behaviors["verbs"] = [
            "class" => \yii\filters\VerbFilter::class,
            "actions" => [
                'index' => [ 'get'],
                'create' => [ 'post' ],
                'update' => [ 'put', 'post' ],
                'delete' => [ 'delete' ],
                'get-customer' => [ 'get' ],
                'customers' => [ 'get' ],
            ]   
        ];
        $behaviors['authenticator'] = [         	
            'class' => \yii\filters\auth\HttpBearerAuth::class,         	
            'except' => ['options']     	
        ];
        return $behaviors;

    }

    public function beforeAction( $action ){
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
        
        for ($i=0; $i < count($printSpoolerGlobal) ; $i++) { 
            $print = $printSpoolerGlobal[$i];
            $sale = Venta::find()
            ->select(['venta.*', 'usuario.username', 'mesa.nombre as mesa', 'cliente.nombre as cliente'])
            ->innerJoin('usuario','usuario.id = venta.usuario_id')
            ->innerJoin('mesa','mesa.id = venta.mesa_id')
            ->innerJoin('cliente','cliente.id = venta.cliente_id')
            ->where(['venta.id' => $print-> venta_id])
            ->asArray()
            ->one();
            /* Si es cocina, solo agregamos los nuevos pedidios, si es salon todos */
            $orderDetail = [];

            if($print -> area == 'cocina'){
                $orderDetail = DetalleVenta::find()
                                ->select(['detalle_venta.cantidad', 'producto.*'])
                                ->where(['venta_id' => $print -> venta_id, 'detalle_venta.estado' => 'nuevo'])
                                ->innerJoin('producto' , 'producto.id=detalle_venta.producto_id')
                                ->asArray()
                                ->all();
                /* actulizar el estado de nuevo a enviado */
                for($i = 0; $i < count($orderDetail); $i++){
                    $order = $orderDetail[$i];
                    $order -> estado = 'enviado';
                    if(!$order -> save()){
                        return [
                            'success' => false,
                            'message' => 'Existen errores en los parametros',
                            'errros' => $order -> errors
                        ];
                    }
                }
            }else{
                $orderDetail = DetalleVenta::find()
                                ->select(['detalle_venta.cantidad', 'producto.*'])
                                ->where(['venta_id' => $print -> venta_id])
                                ->innerJoin('producto' , 'producto.id=detalle_venta.producto_id')
                                ->asArray()
                                ->all();
            }
            $printer = Impresora::find()->where(['lugar' => $print -> area])->one();
            $infoSale = [
                'nro_pedido' => $sale ["numero_pedido"],
                'nro_mesa' => $sale ["mesa"],
                'cliente' => $sale ["cliente"],
                'orderDetail' => $orderDetail,
                'printerName' => $printer -> nombre,
                'place' => $printer -> lugar,
                'username' => $sale ["username"],
                'cantidad_total' => $sale ['cantidad_total']
            ];
            $print -> estado = true;
            $print -> save();
            $printSpooler[] = $infoSale;
        }
        
        $response = [ 
            'success' => true,
            'message' => 'Cola de impresions',
            'printSpooler' => $printSpooler
            
        ];
        return $response;
    }
    public function actionCreate(){
        $params = Yii::$app-> getRequest() -> getBodyParams();
        $printSpooler = new ColaImpresion();
        $printSpooler -> load($params, '');

        if($printSpooler -> save()){
            $response = [ 
                'success' => true,
                'message' => 'Se agrego correctamente',
            ];
        }else{
            $response = [ 
                'success' => false,
                'message' => 'Ocurrio un error',
                'errors' => $printSpooler -> errors 
            ];
        }
        return $response;
    }

}
