<?php

namespace app\controllers;

use app\models\CatalogoGrupoModificadores;
use Yii;

class CatalogoGrupoModificadoresController extends \yii\web\Controller
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

    public function actionIndex(){
        $query = CatalogoGrupoModificadores::find() -> all();
    
        $response = [
            'success' => true,
            'message' => '',
            'records' => $query
        ];
        return $response;
    }

    public function actionCreate(){
        $params = Yii::$app -> getRequest() -> getBodyParams();

        $model = new CatalogoGrupoModificadores();
        $model->load($params, '');
        $model -> save();

        $response = [
            'success' => true,
            'message' => 'Grupo de modificadores creado',
            'record' => $model
        ];
        return $response;
    }
}
