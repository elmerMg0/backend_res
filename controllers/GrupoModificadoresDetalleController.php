<?php

namespace app\controllers;

use app\models\GrupoModificadores;
use app\models\GrupoModificadoresDetalle;
use Exception;
use Yii;

class GrupoModificadoresDetalleController extends \yii\web\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors["verbs"] = [
            "class" => \yii\filters\VerbFilter::class,
            "actions" => [
                'index' => ['get'],
                'create-expense' => ['post'],
                'update' => ['put', 'post'],
                'get-expenses' => ['GET'],
            ]
        ];
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['options']
        ];
        $behaviors['access'] = [
            'class' => \yii\filters\AccessControl::class,
            'only' => ['create-expense', 'get-expenses'], // acciones a las que se aplicará el control
            'except' => [''],    // acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['create-expense', 'get-expenses'], // acciones que siguen esta regla
                    'roles' => ['administrador', 'mesero', 'cajero'] // control por roles  permisos
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

    public function actionIndex($id){
        $query = GrupoModificadoresDetalle::find()
                        ->select(['grupo_modificadores_detalle.*', 'producto.nombre as descripcion'])
                        ->where(['grupo_modificadores_id' => $id])
                        ->innerJoin('producto', 'producto.id = grupo_modificadores_detalle.producto_id')
                        ->asArray()
                        ->all();

        $response = [
            'success' => true,
            'message' => '',
            'records' => $query
        ];
        return $response;
    }

    public function actionUpdate (){
        $params = Yii::$app -> getRequest() ->getBodyParams();
        extract($params);
        $transaction = Yii::$app->db->beginTransaction();
        try{
            for($i = 0; $i < count($modificators); $i++){
                $model = GrupoModificadoresDetalle::findOne($modificators[$i]['id']);
                if(!$model){
                    $model = new GrupoModificadoresDetalle();
                }
                $model->load($modificators[$i], '');
                if(!$model->save()){
                    throw new Exception(json_encode($model->errors));   
                }
            }


            for($i = 0; $i < count($modificatorsToDelete); $i++){
                $model = GrupoModificadoresDetalle::findOne($modificatorsToDelete[$i]);
                if(!$model->delete()){
                    throw new Exception(json_encode($model->errors));
                }
            }

            $transaction->commit();
            $response = [
                'success' => true,
                'message' => 'Grupo de modificadores actualizado',
                'records' => $modificators
            ];
        }catch(Exception $e){
            $transaction->rollBack();
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
        return $response;
    }
}
