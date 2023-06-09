<?php

namespace app\controllers;

use app\models\Cliente;
use app\models\Delivery;
use Exception;
use Yii;
use yii\data\Pagination;

class DeliveryController extends \yii\web\Controller
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
                'get-delivery' => [ 'get' ],
                'deliveries' => [ 'get' ],
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


    public function actionIndex( $pageSize = 5)
    {
        $query = Delivery::find();

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count(),
        ]);

        $deliveries = $query
                        ->orderBy('id DESC')
                        ->offset($pagination->offset)
                        ->limit($pagination->limit)        
                        ->all();
        
        $currentPage = $pagination->getPage() + 1;
        $totalPages = $pagination->getPageCount();
        $response = [
        'success' => true,
        'message' => 'lista de Deliverys',
        'pageInfo' => [
            'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
            'previus' => $currentPage == 1 ? null: $currentPage - 1,
            'count' => count($deliveries),
            'page' => $currentPage,
            'start' => $pagination->getOffset(),
            'totalPages' => $totalPages,
            'deliveries' => $deliveries
            ]
        ];
        return $response;
    }
    public function actionDeliveries(){
        $deliveries = Delivery::find()->all();
        $response = [
            'success' => true,
            'message' => 'Todos los clientes',
            'deliveries' => $deliveries
        ];
        return $response;
    }

    public function actionCreate(){
        $params = Yii::$app->getRequest()->getBodyParams();
        $delivery = new Delivery();
        $delivery -> load($params,"");
     /*    $delivery -> fecha_crecion = Date("H-m-d H:i:s") */;
        try{
            if($delivery->save()){
                //todo ok
                Yii::$app->getResponse()->setStatusCode(201);
                $response = [
                    "success" => true,
                    "message" => "delivery agreado exitosamente",
                    'delivery' => $delivery
                ];
            }else{
                //Cuando hay error en los tipos de datos ingresados 
                Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed');
                $response = [
                    "success" => false,
                    "message" => "Existen parametros incorrectos",
                    'errors' => $delivery->errors
                ];
            }
        }catch(Exception $e){
        //cuando no se definen bien las reglas en el modelo ocurre este error, por ejemplo required no esta en modelo y en la base de datos si, 
        //existe incosistencia
            $response = [
                "success" => false,
                "message" => "ocurrio un error",
                'errors' => $e
            ];
        }
        return $response;
    }

    public function actionUpdate( $idDelivery ){
        $delivery = Delivery::findOne($idDelivery);
        if($delivery){
            $params = Yii::$app->getRequest()->getBodyParams();
            $delivery -> load($params, '');
                try{

                    if($delivery->save()){
                        $response = [
                            'success' => true,
                            'message' => 'Cliente actualizado correctamente',
                            'delivery' => $delivery
                        ];
                    }else{
                        Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed');
                        $response = [
                            'success' => false,
                            'message' => 'Existe errores en los campos',
                            'error' => $delivery->errors
                        ];
                    }
                }catch(Exception $e){
                    $response = [
                        'success' => false,
                        'message' => 'Delivery no encontrado',
                    ];
                }
        }else{
            $response = [
                'success' => false,
                'message' => 'Delivery no encontrado',
            ];
        }
        return $response;
    }

    public function actionGetDelivery($idDelivery){
        $delivery = Delivery::findOne($idDelivery);
        if($delivery){
            $response = [
                'success' => true,
                'message' => 'Accion realizada correctamente',
                'delivery' => $delivery
            ];
        }else{
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'No existe el cliente',
                'delivery' => $delivery
            ];
        }
        return $response;
    }

    public function actionDelete( $idDelivery ){
        $delivery = Cliente::findOne($idDelivery);

        if($delivery){
            try{
                $delivery->delete();
                $response = [
                    "success" => true,
                    "message" => "Delivery eliminado correctamente",
                    "data" => $delivery
                ];
            }catch(yii\db\IntegrityException $ie){
                Yii::$app->getResponse()->setStatusCode(409, "");
                $response = [
                    "success" => false,
                    "message" =>  "El delivery esta siendo usado",
                    "code" => $ie->getCode()
                ];
            }catch(\Exception $e){
                Yii::$app->getResponse()->setStatusCode(422, "");
                $response = [
                    "success" => false,
                    "message" => $e->getMessage(),
                    "code" => $e->getCode()
                ];
            }
        }else{
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                "success" => false,
                "message" => "Delivery no encontrado"
            ];
        }
        return $response;
    }

}
