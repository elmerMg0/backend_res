<?php

namespace app\controllers;

use app\models\Insumo;
use app\models\Receta;
use Exception;
use Yii;
use yii\data\Pagination;

class InsumoController extends \yii\web\Controller
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
                'get-supplies' => ['get'],

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

    public function actionIndex($description, $pageSize = 5)
    {
        $description = isset($description) ? $description : null;
        $query = Insumo::find()
            ->select(['insumo.id', 'insumo.grupo_insumo_id', 'insumo.descripcion', 'insumo.estado', 'grupo_insumo.descripcion as grupo_insumo', 'unidad_medida.nombre as unidad_medida', 'ultimo_costo', 'inventariable', 'costo_promedio', 'porcentaje_merma', 'alerta_existencias', 'unidad_medida_id', 'ultimo_costo_c_merma'])
            ->innerJoin('grupo_insumo', 'grupo_insumo.id = insumo.grupo_insumo_id')
            ->innerJoin('unidad_medida', 'unidad_medida.id = insumo.unidad_medida_id')
            ->andFilterWhere(['LIKE', 'UPPER(insumo.descripcion)',  strtoupper($description)]);

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
            'message' => 'lista de insumos',
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

    public function actionGetSupplies($estado = null)
    {
        $records = Insumo::find()
            ->select(['insumo.*', 'unidad_medida.abreviatura as unidad_medida', 'insumo.id as insumo_id'])
            ->filterWhere(['estado' => $estado])
            ->innerJoin('unidad_medida', 'insumo.unidad_medida_id = unidad_medida.id')
            ->orderBy(['id' => 'SORT_ASC'])
            ->asArray()
            ->all();

        if ($records) {
            $response = [
                'success' => true,
                'message' => 'Lista de insumos',
                'records' => $records,
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'No existen insumos',
                'records' => [],
            ];
        }
        return $response;
    }

    public function actionCreate()
    {
        $supplies = new Insumo();
        $params = Yii::$app->getRequest()->getBodyParams();
        try {
            extract($params);
            $supplies->load($params, '');
            $supplies->alerta_existencias = $alerta_existencias ? true : false;
            $supplies->costo_promedio = floatval($ultimo_costo);
            $supplies->ultimo_costo_c_merma = number_format(floatval($ultimo_costo) + ((floatval($porcentaje_merma) / 100) * floatval($ultimo_costo)), 2);
            if ($supplies->save()) {
                Yii::$app->getResponse()->setStatusCode(201);
                $response = [
                    'success' => true,
                    'message' => 'Insumo creado exitosamente',
                    'record' => $supplies
                ];
            } else {
                Yii::$app->getResponse()->setStatusCode(422, "Data Validation Failed.");
                $response = [
                    'success' => false,
                    'message' => 'Existen errores en los campos',
                    'errors' => $supplies->errors
                ];
            }
        } catch (Exception $e) {
            Yii::$app->getResponse()->setStatusCode(500);
            $response = [
                'success' => false,
                'message' => 'ocurrio un error',
                'record' => $e->getMessage()
            ];
        }

        return $response;
    }

    public function updateSuppliesCost($idSupplies, $params, $performance = 1){
        extract($params);
        $supplies = Insumo::findOne($idSupplies);
        $supplies->load($params, '');
        if($supplies -> costo_promedio === "0.00"){
            $supplies->costo_promedio =  number_format(floatval($ultimo_costo), 2);
        }else{
            $supplies->costo_promedio = number_format(($supplies->costo_promedio + floatval($ultimo_costo)) / 2, 2);
        }
        
        $supplies->ultimo_costo_c_merma = number_format(floatval($ultimo_costo) / (($performance - ($supplies -> porcentaje_merma / 100))), 2);
        return $supplies;
    }

    public function actionUpdate($idSupplies)
    {
        if ($idSupplies) {
            $params = Yii::$app->getRequest()->getBodyParams();
            $supplies = $this -> updateSuppliesCost($idSupplies, $params);
            try {
                if ($supplies->save()) {
                    $response = [
                        'success' => true,
                        'message' => 'insumos actualizado correctamente',
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
                    'message' => 'Categoria no encontrado',
                    'error' => $e->getMessage()
                ];
            }
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'Categoria no encontrado',
            ];
        }
        return $response;
    }

    public function actionSupplies($idSupplies)
    {
        $supplies = Insumo::find()
            ->select(['insumo.*', 'unidad_medida.nombre as unidad_medida', 'grupo_insumo.descripcion as grupo_insumo'])
            ->where(['insumo.id' => $idSupplies])
            ->innerJoin('unidad_medida', 'insumo.unidad_medida_id = unidad_medida.id')
            ->innerJoin('grupo_insumo', 'insumo.grupo_insumo_id = grupo_insumo.id')
            ->asArray()
            ->one();
        if ($supplies) {
            $response = [
                'success' => true,
                'message' => 'Insumo',
                'record' => $supplies
            ];
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'No existe el Insumo',
                'record' => $supplies
            ];
        }
        return $response;
    }

    public function actionDisable($idSupplies)
    {
        $supplies = Insumo::findOne($idSupplies);
        if ($supplies) {
            $supplies->estado = false;
            if ($supplies->save()) {
                $response = [
                    'success' => true,
                    'message' => 'Insumo actualizado'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Ocurrio un error!'
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

    public function actionGetSuppliesByProduct($idProduct)
    {
        $records = Receta::find()
            ->select(['insumo.id', 'insumo.descripcion', 'cantidad', 'unidad_medida.abreviatura as unidad_medida', 'ultimo_costo', 'costo_promedio', 'porcentaje_merma', 'receta.id as receta_id', 'insumo.ultimo_costo_c_merma'])
            ->where(['producto_id' => $idProduct])
            ->innerJoin('insumo', 'insumo.id = receta.insumo_id')
            ->innerJoin('unidad_medida', 'insumo.unidad_medida_id = unidad_medida.id')
            ->asArray()
            ->all();
        $response = [
            'success' => true,
            'message' => 'Receta de producto',
            'records' => $records
        ];
        return $response;
    }
}
