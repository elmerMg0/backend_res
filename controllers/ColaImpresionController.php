<?php

namespace app\controllers;

use app\models\ColaImpresion;
use Yii;

class ColaImpresionController extends \yii\web\Controller
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
                'get-customer' => [ 'get' ],
                'customers' => [ 'get' ],
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
    /* Retorna las ventas, que no esten finalizadas, con los productos mas nuevos.   */
    public function actionIndex()
    {
        $cola = ColaImpresion::find()-> all();
    }

}
