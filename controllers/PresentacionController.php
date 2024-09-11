<?php

namespace app\controllers;

use app\models\Insumo;
use app\models\Inventario;
use app\models\InventarioPres;
use app\models\Presentacion;
use Exception;
use Yii;
use yii\data\Pagination;

class PresentacionController extends \yii\web\Controller
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

    public function actionIndex($description, $pageSize = 5)
    {
        $description = isset($description) ? $description : null;
        $query = Presentacion::find()
            ->select(['presentacion.*', 'proveedor.nombre as proveedor'])
            ->innerJoin('proveedor', 'proveedor.id = presentacion.proveedor_id')
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

    public function updateSuppliesCost($params, $idSupplies)
    {
        extract($params);
        $supplies  = new InsumoController('', '');
        $lastCost = number_format($ultimo_costo / $rendimiento, 2);
        $supplies = $supplies->updateSuppliesCost($idSupplies, ['ultimo_costo' => $lastCost], 1);
        return $supplies;
    }

    public function actionCreate()
    {
        $presentation = new Presentacion();
        $params = Yii::$app->getRequest()->getBodyParams();
        extract($params);
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $presentation->load($params, '');
            if (floatval($ultimo_costo) > 0) {
                $presentation->costo_promedio = $ultimo_costo;
                $supplies = $this->updateSuppliesCost($params, $insumo_id,);
                if (!$supplies->save()) {
                    throw new Exception(json_encode($supplies->errors));
                } 
            }

            if(!$presentation->save()){
                throw new Exception(json_encode($presentation->errors));
            }

            $response = [
                'success' => true,
                'message' => 'Presentación creado exitosamente',
                'record' => $presentation
            ];
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::$app->getResponse()->setStatusCode(500);
            $response = [
                'success' => false,
                'message' => 'ocurrio un error',
                'error' => $e->getMessage()
            ];
        }

        return $response;
    }


    public function updatePresentation($idPresentation, $params)
    {
        extract($params);
        $presentation = Presentacion::findOne($idPresentation);
        $presentation->load($params, '');
        $presentation->costo_promedio = number_format(($presentation->costo_promedio + $ultimo_costo) / 2, 2);
        return $presentation;
    }

    public function actionUpdate($idPresentation)
    {
        //$presentation = Presentacion::findOne($idPresentation);
        if ($idPresentation) {
            $params = Yii::$app->getRequest()->getBodyParams();
            extract($params);
            try {
                //$presentation->load($params, '');
                $presentation = $this->updatePresentation($idPresentation, $params);
                // $presentation->costo_promedio = number_format(($presentation -> costo_promedio + $ultimo_costo) / 2 , 2);

                if ($ultimo_costo > 0) {
                    $supplies = $this->updateSuppliesCost($params, $insumo_id,);
                    /*  $supplies  = new InsumoController('','');
                    $lastCost = number_format($ultimo_costo / $rendimiento, 2);
                    $supplies = $supplies -> updateSuppliesCost($insumo_id, array_merge($params, ['ultimo_costo' => $lastCost], $rendimiento)); */
                    //$supplies->ultimo_costo = $lastCost;
                    //$supplies->costo_promedio = number_format(($supplies -> costo_promedio + $lastCost)/2, 2);
                    //$supplies->ultimo_costo_c_merma = number_format($ultimo_costo / ($rendimiento * (1 - ($supplies->porcentaje_merma / 100))), 2);
                    $supplies->save();
                }
                if ($presentation->save()) {
                    $response = [
                        'success' => true,
                        'message' => 'Presentacion actualizados correctamente',
                        'presentation' => $presentation
                    ];
                } else {
                    Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed');
                    $response = [
                        'success' => false,
                        'message' => 'Existe errores en los campos',
                        'error' => $presentation->errors
                    ];
                }
            } catch (Exception $e) {
                Yii::$app->getResponse()->setStatusCode(500);
                $response = [
                    'success' => false,
                    'message' => 'Presentación no encontrado',
                    'error' => $e->getMessage()
                ];
            }
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'Presentación no encontrado',
            ];
        }
        return $response;
    }

    public function actionGetPresentationsBySupplies($idSupplies)
    {
        $presentations = Presentacion::find()
            ->select(['presentacion.*', 'proveedor.nombre as proveedor'])
            ->where(['insumo_id' => $idSupplies])
            ->innerJoin('proveedor', 'proveedor.id = presentacion.proveedor_id')
            ->asArray()
            ->orderBy(['presentacion.id' => SORT_DESC])
            ->all();
        $response = [
            'success' => true,
            'message' => 'Presentaciones de insumo',
            'records' => $presentations
        ];
        return $response;
    }

    public function actionPresentations($estado = null)
    {
        $presentations = Presentacion::find()
            ->select([
                'presentacion.id',
                'presentacion.descripcion',
                'presentacion.ultimo_costo',
                'presentacion.costo_promedio',
                'presentacion.insumo_id',
                'presentacion.rendimiento',
                'insumo.alerta_existencias',
                'presentacion.stock_maximo',
                'presentacion.id as presentacion_id',
                'unidad_medida.abreviatura as unidad_medida',
            ])
            ->filterWhere(['presentacion.estado' => $estado])
            ->innerJoin('insumo', 'insumo.id = presentacion.insumo_id')
            ->innerJoin('unidad_medida', 'unidad_medida.id = insumo.unidad_medida_id')
            ->asArray()
            ->all();

        $insumoIds = array_column($presentations, 'insumo_id');
        $insumoInventarios = Inventario::find()
        ->where(['insumo_id' => $insumoIds])
        ->asArray()
        ->all();
        
        $insumoCantidades = [];
        foreach ($insumoInventarios as $inventario) {
            if (!isset($insumoCantidades[$inventario['insumo_id']])) {
                $insumoCantidades[$inventario['insumo_id']] = 0;
            }
            $insumoCantidades[$inventario['insumo_id']] += $inventario['cantidad'];
        }

        $presentacionIds = array_column($presentations, 'id');
        $presentacionInventarios = InventarioPres::find()
            ->where(['presentacion_id' => $presentacionIds])
            ->asArray()
            ->all();

        $presentacionCantidades = [];
        foreach ($presentacionInventarios as $inventario) {
            if (!isset($presentacionCantidades[$inventario['presentacion_id']])) {
                $presentacionCantidades[$inventario['presentacion_id']] = 0;
            }
            $presentacionCantidades[$inventario['presentacion_id']] += $inventario['cantidad'];
        }

        // Actualiza las cantidades en el array de presentaciones
        foreach ($presentations as &$present) {
            $present['cantidad_presentacion'] = $insumoCantidades[$present['insumo_id']] ?? 0;
            $present['cantidad_insumo'] = ($presentacionCantidades[$present['id']] ?? 0) * $present['rendimiento'];
        }

        $response = [
            'success' => true,
            'message' => 'Lista de Presentaciones',
            'records' => $presentations
        ];
        return $response;
    }

    public function actionDelete($id)
    {
        $presentation = Presentacion::findOne($id);
        if ($presentation) {

            try {
                $presentation->delete();
                $response = [
                        'success' => true,
                        'message' => 'Presentación eliminado correctamente',
                    ];
            } catch (Exception $e) {
                $response = [
                    'success' => false,
                    'message' => 'No se puede eliminar la Presentación porque tiene registros relacionados'
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
