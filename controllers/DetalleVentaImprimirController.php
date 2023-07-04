<?php

namespace app\controllers;

use app\models\DetalleVentaImprimir;
use Yii;
class DetalleVentaImprimirController extends \yii\web\Controller
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

     /*    $behaviors['access'] = [
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
        ]; */
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

    public function actionCreate( $idPrintSpooler ){
        $params = Yii::$app-> getRequest()->getBodyParams();
        $orderDetails = $params['orderDetail'];
        for($i = 0; $i < count($orderDetails); $i++){
            $orderDetail = $orderDetails[$i];
            $order = New DetalleVentaImprimir();
            $order -> cantidad = $orderDetail["cantidad"];
            $order -> producto_id = $orderDetail ["id"];
            $order -> cola_impresion_id = $idPrintSpooler;
            
            if( !$order -> save()){
                $response = [
                    'success' => false,
                    'message' => 'Existen errores en los paramtreos',
                    'errors' => $order -> errors
                ];
            }
        }

        $response = [
            'success' => true,
            'message' => 'Detalle de venta agregado exitosamente',
        ];

        return $response;
    }

}
