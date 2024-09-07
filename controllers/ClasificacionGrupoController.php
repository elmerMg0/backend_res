<?php

namespace app\controllers;

use app\models\ClasificacionGrupo;
use Yii;

class ClasificacionGrupoController extends \yii\web\Controller
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
                'get-category' => ['get'],

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

    public function actionGroupsClassification () {
        $records = ClasificacionGrupo::find()
                      ->where(['estado' => true])
                      ->orderBy(['id' => 'SORT_ASC'])             
                      ->all();

        if($records){
            $response = [
                'success' => true,
                'message' => 'Lista de clasificaciones de grupo de insumos',
                'records' => $records,
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'No existen registros',
                'records' => [],
            ];
        }
        return $response;
    }

}
