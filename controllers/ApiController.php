<?php

namespace app\controllers;

use Yii;
use app\models\Periodo;
use app\models\Categoria;
use app\models\Venta;
use app\models\DetalleVenta;
class ApiController extends \yii\web\Controller{
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
                'get-category' => ['get'],

            ]
        ];
       /*  $behaviors['authenticator'] = [         	
            'class' => \yii\filters\auth\HttpBearerAuth::class,         	
            'except' => ['options']     	
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
    
    public function actionExistsPeriod(){
        $period = Periodo::find()
                        ->where(['estado' => true])
                        ->one();
        if($period){
                $response = [
                    'success' => true, 
                    'message' => 'existe periodo activo',
                ];
        }else{
            $response = [
                'success' => false, 
                'message' => 'En este instante está fuera de los horarios de atención',
            ];
        }
        return $response;
    }

    public function actionGetCategories () {
        $categories = Categoria::find()
                      ->orderBy(['id' => 'SORT_ASC'])  
                      ->where(['estado' => 'Activo', 'cortesia' => false])   
                     ->all();

        if($categories){

            $response = [
                'success' => true,
                'message' => 'Lista de categorias',
                'categories' => $categories,
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'No existen categorias',
                'categories' => [],
            ];
        }

        return $response;
    }

    public function actionGetCategoryWithProducts(){
        $query = Categoria::find()
                    ->where(['categoria.estado' => 'Activo', 'categoria.cortesia' => false])
                    ->with(['productos' => function ($query) {
                        $query->andWhere(['estado' => 'Activo', 'cortesia' => false]);
                    }])
                    ->orderBy(['id' => 'SORT_ASC'])
                    ->asArray()
                    ->all();
        if($query){
            $response = [
                'success' => true,
                'message' => 'Lista de categorias',
                'categories' => $query
            ];
        }else{
            $response = [
                'success' => true,
                'message' => 'Lista de categorias',
                'categories' => []
            ];
        }
        return $response;
    }

    public function actionCreate($userId=0)
    {
        /* SI es pedido de app, entonces la venta se carga al ultimo periodo aperturado */
        if($userId === 0 ){
            $period = Periodo::find()
                           ->where(['estado' => true])
                           ->one();
            if($period){
                $userId = $period -> usuario_id;
            }else{
                return  [
                    'success' => false,
                    'message' => 'Ocurrio un error',
                ];
            }
        }

        $params = Yii::$app->getRequest()->getBodyParams();
        $numberOrder = Venta::find()->all();
        $orderDetail = $params['orderDetail'];
        $sale = new Venta();
        date_default_timezone_set('America/La_Paz');
        $sale -> fecha = date('Y-m-d H:i:s');
        $sale->cantidad_total = intval($params['cantidadTotal']);
        $sale->cantidad_cancelada = $params['cantidadPagada'];
        $sale->usuario_id = $userId;
        $sale->numero_pedido = count($numberOrder) + 1;
        $sale->estado = $params['estado'];
        $sale->tipo_pago = $params['tipoPago'];
        $sale->tipo = $params['tipo'];
        $sale->nombre = $params['nombre'];
        if($params['tipo'] === 'pedidoApp'){
            $sale->tipo_entrega = $params['tipo_entrega'];
            $sale->telefono = $params['telefono'];
            $sale->hora = $params['hora'];
            $sale->descripcion_direccion = $params['descripcion_direccion'];
            $sale->direccion = $params['direccion'];
        }
        //$sale->cliente_id = $customerId;

        if ($sale->save()) {
            //agregar detalle de venta
            foreach ($orderDetail as $order) {
                $saleDetail = new DetalleVenta();
                $saleDetail->cantidad = $order['cantidad'];
                $saleDetail->producto_id = $order['id'];
                $saleDetail->venta_id =  $sale->id;
                if ($saleDetail->save()) {
                   
                }else{
                    Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed.');
                    return $response = [
                        'success' => false,
                        'message' => 'Existen errores en los parametros',
                        'data' => $saleDetail->errors
                    ];
                }
            }

            Yii::$app->getResponse()->setStatusCode(201);
            $response = [
                'success' => true,
                'message' => 'Su pedido se realizo exitosamente',
                'sale' => $sale
            ];
        } else {
            Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed.');
            $response = [
                'success' => false,
                'message' => 'failed update',
                'data' => $sale->errors
            ];
        }
        return $response;
    }
}