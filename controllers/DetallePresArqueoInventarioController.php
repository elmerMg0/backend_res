<?php

namespace app\controllers;

use app\models\DetallePresArqueoInventario;
use Yii;

class DetallePresArqueoInventarioController extends \yii\web\Controller
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors["verbs"] = [
            "class" => \yii\filters\VerbFilter::class,
            "actions" => [
                'index' => ['post'],
                'create' => ['post'],
                'update' => ['put', 'post'],
            ]
        ];
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['options']
        ];
        $behaviors['access'] = [
            'class' => \yii\filters\AccessControl::class,
            'only' => ['create', 'index'], // acciones a las que se aplicará el control
            'except' => [''],    // acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['index', 'create'], // acciones que siguen esta regla
                    'roles' => ['administrador', 'configurador'] // control por roles  permisos
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

    public function actionDetails($id){
        $inventaryDetails = DetallePresArqueoInventario::find()
                            ->select(['presentacion.descripcion', 'detalle_pres_arqueo_inventario.*', 'unidad_medida.abreviatura as unidad_medida'])
                            ->innerjoin('presentacion', 'detalle_pres_arqueo_inventario.presentacion_id = presentacion.id')
                            ->innerJoin('insumo', 'presentacion.insumo_id = insumo.id')
                            ->innerjoin('unidad_medida', 'unidad_medida.id = insumo.unidad_medida_id')
                            ->where(['arqueo_inventario_id' => $id])
                            ->asArray()
                            ->all();
        $response = [
            'success' => true,
            'message' => 'Detalle inventario',
            'records' =>  $inventaryDetails
        ];

        return $response;
    }
}
