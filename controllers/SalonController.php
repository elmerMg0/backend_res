<?php

namespace app\controllers;

use app\models\Mesa;
use app\models\Salon;
use Yii;
class SalonController extends \yii\web\Controller
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
        return $this->render('index');
    }

    public function actionCreateLounge(){
        $params = Yii::$app->getRequest()->getBodyParams();
        $lounge = new Salon();
        $lounge -> load($params, '');
        if($lounge -> save()){
            /* Creamos los espacios segun el nrofilas y columnas */
            $nroTables = $params['nro_filas']* $params['nro_columnas'];
            for($i = 0; $i < $nroTables; $i++){
                $table = new Mesa();
                $table -> salon_id = $lounge -> id;
                $table -> nombre = strval( $i + 1);
                if(!$table -> save()){
                    return  [
                        'success' => false,
                        'message' => 'Existen errores en los campos',
                        'errors' => $table->errors
                    ];
                }
            }
            $response = [
                'success' => true,
                'message' => 'Salon creado exitosamente',
                'lounge' => $lounge
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'Existen errores en los campos',
                'errors' => $lounge->errors
            ];
        }
        return $response;
    }

    public function actionGetLounges (){
        $lounges = Salon::find()->all();
        if($lounges){
            $response = [
                'success' => true,
                'message' => 'Lista de salones',
                'lounges' => $lounges
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'Existen errores en los campos',
                'errors' => []
            ];
        }
        return $response;
    }


}
