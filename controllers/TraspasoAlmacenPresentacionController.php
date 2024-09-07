<?php

namespace app\controllers;

use app\models\TraspasoAlmacenPresentacion;
use Exception;
use Yii;

class TraspasoAlmacenPresentacionController extends \yii\web\Controller
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
            ]
        ];
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['options']
        ];
        /* $behaviors['access'] = [
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
        ]; */
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

    public function create($params)
    {
        try {
            $model = new TraspasoAlmacenPresentacion();
            $model->load($params, '');
            if (!$model->save()) {
                throw new Exception(json_encode($model->errors));
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $model;
    }

    public function actionDetails($idTransfer)
    {
        $model = TraspasoAlmacenPresentacion::find()
            ->select(['traspaso_almacen_presentacion.*', 'presentacion.descripcion', 'unidad_medida.abreviatura'])
            ->where(['traspaso_almacen_id' => $idTransfer])
            ->innerJoin('presentacion', 'presentacion.id = presentacion_id')
            ->innerJoin('insumo', 'insumo.id = presentacion.insumo_id')
            ->innerJoin('unidad_medida', 'insumo.unidad_medida_id = unidad_medida.id')
            ->asArray()
            ->all();

        $response = [
            'success' => true,
            'message' => 'Detalle de traspaso Almacen',
            'records' => $model
        ];
        return $response;
    }

}
