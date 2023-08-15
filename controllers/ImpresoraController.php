<?php

namespace app\controllers;

use app\models\Impresora;
use Yii;
class ImpresoraController extends \yii\web\Controller
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

    public function actionIndex()
    {
        $printers = Impresora::find()->all();
        $response = [
            'success' => true,
            'message' => 'Lista de impresoras',
            'printers' => $printers
        ];
        return $response;
    }

    public function actionCreate(){
        $params = Yii::$app->getRequest()->getBodyParams();
        $printer = new Impresora();
        $printer -> load($params, '');
        if($printer -> save()){
            $response = [
                'success' => true,
                'message' => 'Impresora agregada exitosamente.',
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'Existen parametros incorrectos',
                'errors' => $printer -> errors
            ];
        }
        return $response;
    }

    public function actionDelete( $idPrinter ){
        $printer = Impresora::findOne($idPrinter);
        if($printer -> delete()){
            $response = [
                'success' => true,
                'message' => 'Impresora eliminada exitosamente.',
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'Ocurrio un error',
                'errors' => $printer -> errors
            ];
        }
        return $response;
    }

}
