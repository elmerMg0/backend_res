<?php

namespace app\controllers;

use app\models\Notificacion;
use Exception;
use Yii;

class NotificacionController extends \yii\web\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors["verbs"] = [
            "class" => \yii\filters\VerbFilter::class,
            "actions" => [
                'index' => ['get'],
                'delete' => ['get'],
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

    public function actionIndex(){
        $notifications = Notificacion::find()
                                ->where(['leido' => false]) 
                                ->orderBy(['id' => SORT_DESC])    
                                ->all();
        $response = [
            'success' => true,
            'message' => 'Listado de notificaciones',
            'data' => $notifications
        ];

        return $response;
    }

    public function actionDelete($id){
        $notification = Notificacion::findOne($id);
        $notification -> leido = true;
        $notification -> save();

        $response = [
            'success' => true,
            'message' => 'Notificación leida',
            'data' => $notification
        ];

        return $response;
    }
    public function create($params){
        try {
            $model = new Notificacion();
            $model->load($params, '');
            $model->leido = false;
            $model->save();
            if (!$model->save()) {
                throw new Exception(json_encode($model->errors));
            }
            $response = $model;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $response;
    }
}
