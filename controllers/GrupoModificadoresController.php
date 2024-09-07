<?php

namespace app\controllers;

use app\models\GrupoModificadores;
use Exception;
use Yii;

class GrupoModificadoresController extends \yii\web\Controller
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

    public function actionIndex($idProduct)
    {
        $query = GrupoModificadores::find()
            ->select(['grupo_modificadores.*', 'catalogo_grupo_modificadores.descripcion'])
            ->innerJoin('catalogo_grupo_modificadores', 'catalogo_grupo_modificadores.id = grupo_modificadores.catalogo_grupo_modificadores_id')
            ->where(['producto_id' => $idProduct])
            ->orderBy(['grupo_modificadores.id' => SORT_ASC])
            ->asArray()
            ->all();
        $response = [
            'success' => true,
            'message' => 'Lista de grupos de modificadores',
            'records' => $query
        ];
        return $response;
    }

    public function actionUpdate()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        extract($params);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            for ($i = 0; $i < count($modificadores); $i++) {
                $model = new GrupoModificadores();
                if(isset($modificadores[$i]['producto_id'])){
                    $model = GrupoModificadores::findOne($modificadores[$i]['id']);
                }
                $model->load($modificadores[$i], '');
                $model->producto_id = $idProduct;
                if (!$model->save()) {
                    throw new Exception(json_encode($model->errors));
                }
            }

            for($i = 0; $i < count($modificatorsToDelete); $i++){
                $model = GrupoModificadores::findOne($modificatorsToDelete[$i]);
                if(!$model->delete()){
                    throw new Exception(json_encode($model->errors));
                }
            }


            $transaction->commit();
            $response = [
                'success' => true,
                'message' => 'Grupo de modificadores actualizado',
            ];
        } catch (Exception $e) {
            $transaction->rollBack();
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
            return $response;
        }

        $response = [
            'success' => true,
            'message' => 'Grupo de modificadores creado',
            'record' => $model
        ];
        return $response;
    }
}
