<?php

namespace app\controllers;

use app\models\Proveedor;
use Exception;
use Yii;
use yii\data\Pagination;

class ProveedorController extends \yii\web\Controller
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

    public function actionIndex($name = null, $pageSize = 5)
    {
        $query = Proveedor::find()
            ->andFilterWhere(['LIKE', 'UPPER(proveedor.name)',  strtoupper($name)]);

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count(),
        ]);

        $records = $query
            ->orderBy('id DESC')
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->asArray()
            ->all();

        $currentPage = $pagination->getPage() + 1;
        $totalPages = $pagination->getPageCount();
        $response = [
            'success' => true,
            'message' => 'lista de proveedores',
            'pageInfo' => [
                'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                'previus' => $currentPage == 1 ? null : $currentPage - 1,
                'count' => count($records),
                'page' => $currentPage,
                'start' => $pagination->getOffset(),
                'totalPages' => $totalPages,
            ],
            'records' => $records
        ];
        return $response;
    }

    public function actionGetSuppliers($estado = null)
    {
        $records = Proveedor::find()
            ->filterWhere(['estado' => $estado])
            ->orderBy(['id' => 'SORT_ASC'])
            ->all();
        if ($records) {
            $response = [
                'success' => true,
                'message' => 'Lista de proveedores',
                'records' => $records,
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'No existen proveedores',
                'records' => [],
            ];
        }
        return $response;
    }

    public function actionCreate()
    {
        $supplier = new Proveedor();
        $params = Yii::$app->getRequest()->getBodyParams();

        try {
            $supplier->load($params, '');
            if ($supplier->save()) {
                Yii::$app->getResponse()->setStatusCode(201);
                $response = [
                    'success' => true,
                    'message' => 'Proveedor creado exitosamente',
                    'fileName' => $supplier
                ];
            } else {
                Yii::$app->getResponse()->setStatusCode(422, "Data Validation Failed.");
                $response = [
                    'success' => false,
                    'message' => 'Existen errores en los campos',
                    'errors' => $supplier->errors
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


    public function actionUpdate($idSupplier)
    {
        $supplier = Proveedor::findOne($idSupplier);
        if ($supplier) {
            $data = Yii::$app->getRequest()->getBodyParams();
            $supplier->load($data, '');
            try {
                if ($supplier->save()) {
                    $response = [
                        'success' => true,
                        'message' => 'Proveedor actualizado correctamente',
                        'supplier' => $supplier
                    ];
                } else {
                    Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed');
                    $response = [
                        'success' => false,
                        'message' => 'Existe errores en los campos',
                        'error' => $supplier->errors
                    ];
                }
            } catch (Exception $e) {
                Yii::$app->getResponse()->setStatusCode(500);
                $response = [
                    'success' => false,
                    'message' => 'Proveedor no encontrado',
                    'error' => $e->getMessage()
                ];
            }
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'Proveedor no encontrado',
            ];
        }
        return $response;
    }

    public function actionDelete($idSupplier)
    {
        $supplier = Proveedor::findOne($idSupplier);
        if ($supplier) {
            try {
                if ($supplier->delete()) {
                    $response = [
                        'success' => true,
                        'message' => 'Proveedor eliminado exitosamente',
                    ];
                }
            } catch (Exception $e) {
                Yii::$app->getResponse()->setStatusCode(500);
                $response = [
                    'success' => false,
                    'message' => 'No se puede borrar el proveedor, existen registros relacionados',
                    'fileName' => $e->getMessage()
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
