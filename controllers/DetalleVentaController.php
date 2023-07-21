<?php

namespace app\controllers;

use app\models\DetalleVenta;
use Yii;
use app\models\Venta;

class DetalleVentaController extends \yii\web\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors["verbs"] = [
            "class" => \yii\filters\VerbFilter::class,
            "actions" => [
                'index' => ['get'],
                'get-detail-period' => ['GET'],
                'get-sale-detail' => ['GET'],

            ]
        ];
        $behaviors['authenticator'] = [         	
            'class' => \yii\filters\auth\HttpBearerAuth::class,         	
            'except' => ['options']     	
        ];

        $behaviors['access'] = [
            'class' => \yii\filters\AccessControl::class,
            'only' => ['get-best-seller-product', 'index', 'get-sale-detail'], // acciones a las que se aplicará el control
            'except' => [''],    // acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['get-best-seller-product', 'index', 'get-sale-detail'], // acciones que siguen esta regla
                    'roles' => ['administrador', 'cajero'] // control por roles  permisos
                ],
            ],
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

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionGetBestSellerProduct(){
        $detail = DetalleVenta::find()
                    ->select(['sum(cantidad) as cantidad', 'producto.nombre' ])
                    ->join('LEFT JOIN', 'producto', 'producto.id=detalle_venta.producto_id')
                    ->groupBy(['producto_id', 'producto.nombre' ])
                    ->orderBy(['cantidad' => SORT_DESC])
                    ->asArray()
                    ->limit(5)
                    ->all();
        if($detail){
            $response = [
                'success' => true,
                'message' => 'Productos mas vendidos',
                'list' => $detail
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'no existen ventas aun!',
                'list' => $detail
            ];
        }
        return $response;
    }

    public function actionGetSaleDetail( $idSale ){
        $saleInfo = Venta::find()
                    ->select(['venta.*', 'usuario.username', 'cliente.nombre as cliente', 'cliente.celular', 'cliente.direccion', 'cliente.descripcion_domicilio', 'mesa.nombre as mesa'])
                    ->where(['venta.id' => $idSale])
                    ->innerJoin('usuario', 'usuario.id = venta.usuario_id')
                    ->innerJoin('cliente', 'cliente.id = venta.cliente_id')
                    ->innerJoin('mesa','mesa.id = venta.mesa_id')
                    ->asArray()
                    ->one();

        $products = Venta::find($idSale)
                    ->select(['producto.nombre','producto.precio_venta', 'detalle_venta.cantidad'])
                    ->where(['venta.id' => $idSale])
                    ->leftJoin('detalle_venta', 'detalle_venta.venta_id = venta.id')
                    ->leftJoin('producto', 'detalle_venta.producto_id = producto.id')
                    ->asArray()
                    ->all();
        $response = [
            'success' => true, 
            'message' => 'Detalle de venta',
            'saleInfo' => $saleInfo,
            'products' => $products
        ];
        return $response;
    }

}
