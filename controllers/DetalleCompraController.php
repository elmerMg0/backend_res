<?php

namespace app\controllers;

use app\models\DetalleCompra;
use Yii;

class DetalleCompraController extends \yii\web\Controller
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

    public function actionCompra($id){
        $details = DetalleCompra::find()
                                ->select(['detalle_compra.*', 'presentacion.descripcion', 'almacen.descripcion as almacen'])
                                ->where(['compra_id' => $id])
                                ->innerJoin('presentacion', 'presentacion.id = detalle_compra.presentacion_id')
                                ->innerJoin('almacen', 'almacen.id = detalle_compra.almacen_id')
                                ->asArray()
                                ->all();
        return [
            'success' => true,
            'message' => 'ok',
            'records' => $details
        ];
    }
}
