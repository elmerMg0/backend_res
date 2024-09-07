<?php

namespace app\controllers;

use app\models\AreaImpresion;
use Exception;
use Yii;

class AreaImpresionController extends \yii\web\Controller
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
                'get-product' => ['get'],
                'products' => ['post'],
                'disable-product' => ['delete', 'patch']
            ]
        ];
        // add Bearer authentication filter     	
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['options']
        ];

        $behaviors['access'] = [
            'class' => \yii\filters\AccessControl::class,
            'only' => ['get-product', 'products'], // acciones a las que se aplicará el control
            'except' => [],    // acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['index', 'update', 'delete', 'create', 'products', 'get-product'], // acciones que siguen esta regla
                    'roles' => ['administrador', 'mesero', 'configurador', 'cajero'] // control por roles  permisos
                ],
                //…
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

    public function actionIndex()
    {
        $area = AreaImpresion::find()
            ->orderBy(['id' => SORT_DESC])
            ->all();

        $response = [
            'success' => true,
            'message' => 'Lista de areas',
            'records' => $area
        ];

        return $response;
    }

    public function actionCreate()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $area = new AreaImpresion();
        $area->load($params, '');

        if ($area->save()) {
            $response = [
                'success' => true,
                'message' => 'Area creada con exito',
                'records' => $area
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Area no creada',
                'records' => $area
            ];
        }

        return $response;
    }

    public function actionUpdate($id)
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $area = AreaImpresion::findOne($id);
        $area->load($params, '');
        if ($area->save()) {
            $response = [
                'success' => true,
                'message' => 'Area actualizada con exito',
                'records' => $area
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Area no actualizada',
                'records' => $area
            ];
        }
        return $response;
    }

    public function actionDelete($id)
    {
        $printerArea = AreaImpresion::findOne($id);
        if ($printerArea) {
            try {
                if ($printerArea->delete()) {
                    $response = [
                        'success' => true,
                        'message' => 'Area eliminado correctamente',
                    ];
                }
            } catch (Exception $e) {
                $response = [
                    'success' => false,
                    'message' => 'No se puede eliminar el área porque tiene registros relacionados'
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'Ocurrio un error!'
            ];
        }
        return $response;
    }
}
