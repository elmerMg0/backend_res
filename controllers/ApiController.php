<?php

namespace app\controllers;

use Yii;
use app\models\Periodo;
use app\models\Categoria;
use app\models\Venta;
use app\models\DetalleVenta;
use app\models\User;
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
                      ->where(['estado' => 'Activo', 'en_ecommerce' => true])   
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
                    ->where(['categoria.estado' => 'Activo', 'categoria.en_ecommerce' => true])
                    ->with(['productos' => function ($query) {
                        $query->andWhere(['estado' => true, 'en_ecommerce' => true]);
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
                           ->all();
            if($period){
                /* Obtener el usuario que sea cajero */
                $userId;
                for($i = 0; $i < count($period); $i++){
                    $user = User::findOne($period[$i] -> usuario_id);
                    if($user -> tipo === 'cajero'){
                        $userId = $user -> id;
                        break;
                    }
                } 
                if(!$userId) $userId = $period[0] -> usuario_id; 
            }else{
                return  [
                    'success' => false,
                    'message' => 'Ocurrio un error',
                ];
            }
        }
        $transaction = Yii::$app->db->beginTransaction();
        try{
            $params = Yii::$app->getRequest()->getBodyParams();
            $numberOrder = Venta::find()->all();
            $orderDetail = $params['orderDetail'];
            $sale = new Venta();
            date_default_timezone_set('America/La_Paz');
            $sale -> fecha = date('Y-m-d H:i:s');
            $sale->cantidad_total = intval($params['cantidadTotal']);
            $sale->cantidad_cancelada = $params['cantidadPagada'];
            $sale->usuario_id = $userId;
            $sale->estado = $params['estado'];
            $sale->numero_pedido = count($numberOrder) + 1;
            $sale->tipo = $params['tipo'];
            $sale->tipo_pago = $params['tipoPago'];
            $sale->nota = $params['nota'];

            if($params['tipo'] === 'pedidoApp'){
                $sale -> info_cliente = $params['cliente'];
                $sale -> mesa_id = 2;
                $sale -> cliente_id = 24;
            }
            if ($sale->save()) {
                //agregar detalle de venta
                foreach ($orderDetail as $order) {
                    $saleDetail = new DetalleVenta();
                    $saleDetail->cantidad = $order['cantidad'];
                    $saleDetail->producto_id = $order['id'];
                    $saleDetail->estado = 'enviado';
                    $saleDetail->impreso = true;
                    $saleDetail->venta_id =  $sale->id;
                    if (!$saleDetail->save()) {
                        throw new \Exception('Error al insertar detalle de venta');
                    }
                }
                $transaction->commit();
                Yii::$app->getResponse()->setStatusCode(201);
                $response = [
                    'success' => true,
                    'message' => 'Su pedido se realizo exitosamente',
                    'sale' => $sale
                ];
            } else {
               throw new \Exception('Error al insertar venta');
            }
        }catch (\Exception $e){
            $transaction->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
        return $response;
    }

    public function actionPing(){
        $start = microtime(true);

        // Medir el tiempo de finalización
        $end = microtime(true);
        $processingTime = ($end - $start) * 1000; // Tiempo en milisegundos

        // Devolver la respuesta con el tiempo de procesamiento
        return [
            'status' => 'success',
            'message' => 'Pong',
            'processing_time' => $processingTime,
        ];
    }
}