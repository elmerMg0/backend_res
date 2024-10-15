<?php

namespace app\controllers;

use app\models\Almacen;
use Exception;
use Yii;

class AlmacenController extends \yii\web\Controller
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
                'get-warehouses' => ['get'],

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

    public function actionIndex($pageSize = 5)
    {
        $query = Almacen::find();
        $records = $query
            ->orderBy('id DESC')
            ->asArray()
            ->all();


        $response = [
            'success' => true,
            'message' => 'lista de almacenes',
            'records' => $records
        ];
        return $response;
    }

    public function actionCreate()
    {
        $warehouse = new Almacen();
        $params = Yii::$app->getRequest()->getBodyParams();
        try {
            $warehouse->load($params, '');

            if ($warehouse->save()) {
                Yii::$app->getResponse()->setStatusCode(201);
                $response = [
                    'success' => true,
                    'message' => 'Almacén creado exitosamente',
                    'fileName' => $warehouse
                ];
            } else {
                Yii::$app->getResponse()->setStatusCode(422, "Data Validation Failed.");
                $response = [
                    'success' => false,
                    'message' => 'Existen errores en los campos',
                    'errors' => $warehouse->errors
                ];
            }
        } catch (Exception $e) {
            Yii::$app->getResponse()->setStatusCode(500);
            $response = [
                'success' => false,
                'message' => 'ocurrio un error',
                'fileName' => $e->getMessage()
            ];
        }

        return $response;
    }


    public function actionUpdate($idWarehouse)
    {
        $supplies = Almacen::findOne($idWarehouse);
        if ($supplies) {
            $params = Yii::$app->getRequest()->getBodyParams();
            $supplies->load($params, '');
            try {
                if ($supplies->save()) {
                    $response = [
                        'success' => true,
                        'message' => 'Almacén actualizado correctamente',
                        'supplies' => $supplies
                    ];
                } else {
                    Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed');
                    $response = [
                        'success' => false,
                        'message' => 'Existe errores en los campos',
                        'error' => $supplies->errors
                    ];
                }
            } catch (Exception $e) {
                Yii::$app->getResponse()->setStatusCode(500);
                $response = [
                    'success' => false,
                    'message' => 'Almacén no encontrado',
                    'error' => $e->getMessage()
                ];
            }
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'Almacén no encontrado',
            ];
        }
        return $response;
    }

    public function actionWarehouses($idCompany = null, $estado = null, $type = null)
    {
        $warehouses = Almacen::find()
            ->filterWhere(['estado' => $estado])
            ->andFilterWhere(['tipo' => $type])
            ->all();
        $response = [
            'success' => true,
            'message' => 'Lista de Almacenes',
            'records' => $warehouses
        ];
        return $response;
    }

    public function actionDelete($idWarehouse)
    {
        $supplies = Almacen::findOne($idWarehouse);
        if ($supplies) {

            try {
                if ($supplies->delete()) {
                    $response = [
                        'success' => true,
                        'message' => 'Almacén eliminado correctamente',
                    ];
                }
            } catch (Exception $e) {
                $response = [
                    'success' => false,
                    'message' => 'No se puede eliminar el almacén porque tiene registros relacionados'
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
