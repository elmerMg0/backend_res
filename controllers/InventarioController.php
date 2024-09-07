<?php

namespace app\controllers;

use app\models\Insumo;
use app\models\Inventario;
use app\models\InventarioPres;
use Exception;
use Yii;
use yii\data\Pagination;

class InventarioController extends \yii\web\Controller
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

    public function actionIndex($idWarehouse = null, $descripcion = null)
    {
        $query = Inventario::find()
            ->select(['inventario.*', 'insumo.descripcion', 'almacen.descripcion as almacen'])
            ->filterWhere(['almacen_id' => $idWarehouse])
            ->andFilterWhere(['like', 'LOWER(insumo.descripcion)', $descripcion])
            ->innerJoin('insumo', 'insumo.id = inventario.insumo_id')
            ->innerJoin('almacen', 'almacen.id = inventario.almacen_id')
            ->orderBy(['inventario.cantidad' => SORT_ASC])
            ->asArray()
            ->all();
        $queryPres = [];
        if (!$idWarehouse) {
            $queryPres = InventarioPres::find()
                ->select(['inventario_pres.*', 'presentacion.descripcion', 'almacen.descripcion as almacen'])
                ->filterWhere(['almacen_id' => $idWarehouse])
                ->andFilterWhere(['like', 'LOWER(presentacion.descripcion)', $descripcion])
                ->innerJoin('presentacion', 'presentacion.id = inventario_pres.presentacion_id')
                ->innerJoin('insumo', 'insumo.id = presentacion.insumo_id')
                ->innerJoin('almacen', 'almacen.id = inventario_pres.almacen_id')
                ->orderBy(['inventario_pres.cantidad' => SORT_ASC])
                ->asArray()
                ->all();
        }

        $response = [
            'success' => true,
            'message' => 'Inventarios insumos',
            'records' => array_merge($query, $queryPres),
        ];

        return $response;
    }

    public function update($params)
    {
        try {
            extract($params);
            $model = Inventario::find()
                ->where(['insumo_id' => $insumo_id, 'almacen_id' => $almacen_id])
                ->one();

            if (!$model) {
                $model = $this->create($insumo_id, $almacen_id);
            }
            $model->cantidad = $model->cantidad + floatval($cantidad);

            if ($model->save()) {
                return $model;
            } else {
                throw new Exception(json_encode($model->errors));
            }
        } catch (Exception $e) {
            throw new Exception(json_encode($e->getMessage()));
        }
    }

    public function create($idSupplies, $idWarehouse)
    {
        $model = new Inventario();
        $model->almacen_id = $idWarehouse;
        $model->insumo_id = $idSupplies;
        $model->cantidad = 0;

        return $model;
    }



    public function actionGetCurrentInventary($pageSize = 10)
    {
        $query = Inventario::find()
            ->select(['producto.nombre', 'producto.stock', 'fecha', 'total', 'nuevo_stock', 'precio_venta', 'precio_compra'])
            ->innerJoin('producto', 'producto.id = inventario.producto_id')
            ->where(['inventario.last_one' => true, 'producto.stock_active' => true]);

        $pagination = new Pagination(
            [
                'defaultPageSize' => $pageSize,
                'totalCount' => $query->count()
            ]
        );

        $inventaries = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->orderBy(['producto.stock' => SORT_ASC])
            ->asArray()
            ->all();
        $currentPage = $pagination->getPage() + 1;
        $totalPages = $pagination->getPageCount();

        $response = [
            'success' => true,
            'inventaries' => $inventaries,
            'pageInfo' => [
                'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                'previus' => $currentPage == 1 ? null : $currentPage - 1,
                'count' => count($inventaries),
                'page' => $currentPage,
                'start' => $pagination->getOffset(),
                'totalPages' => $totalPages,
            ],
        ];

        return $response;
    }

    public function actionRecordsBySuppliesGroup($idWarehouse)
    {
        $params = Yii::$app->getRequest()->getBodyParams();

        $supplies = Insumo::find();
        for ($i = 0; $i < count($params); $i++) {
            $supplies = $supplies
                ->orWhere(['insumo.grupo_insumo_id' => $params[$i]]);
        }

        $supplies = $supplies
            ->andFilterWhere(['inventariable' => true])
            ->all();
        $listIn = [];
        for ($i = 0; $i < count($supplies); $i++) {
            $model  = Inventario::find()
                ->select(['inventario.*', 'insumo.ultimo_costo', 'insumo.descripcion', 'unidad_medida.abreviatura as unidad_medida'])
                ->where(['insumo_id' => $supplies[$i]->id, 'almacen_id' => $idWarehouse])
                ->innerJoin('insumo', 'insumo.id = inventario.insumo_id')
                ->innerJoin('unidad_medida', 'unidad_medida.id = insumo.unidad_medida_id')
                ->asArray()
                ->one();
            if ($model) {
                $listIn[] = $model;
            }
        }

        $response = [
            'success' => true,
            'message' => 'Lista de insumos',
            'records' => $listIn,
        ];
        return $response;
    }

    public function actionSupplies($estado = null)
    {
        $supplies = Inventario::find()
            ->select(['inventario.*', 'insumo.descripcion', 'insumo.ultimo_costo', 'unidad_medida.abreviatura as unidad_medida'])
            ->innerJoin('insumo', 'insumo.id = inventario.insumo_id')
            ->innerJoin('unidad_medida', 'unidad_medida.id = insumo.unidad_medida_id')
            ->filterWhere(['insumo.estado' => $estado])
            ->asArray()
            ->all();
        $response = [
            'success' => true,
            'message' => 'Lista de insumos',
            'records' => $supplies,
        ];
        return $response;
    }
}
