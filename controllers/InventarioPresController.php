<?php

namespace app\controllers;

use app\models\Insumo;
use app\models\Inventario;
use app\models\InventarioPres;
use Exception;
use Yii;

class InventarioPresController extends \yii\web\Controller
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

    public function actionIndex($idWarehouse = null, $descripcion)
    {
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
        $supplies = [];
        if (!$idWarehouse) {
            $supplies = Inventario::find()
                ->select(['inventario.*', 'insumo.descripcion', 'almacen.descripcion as almacen'])
                ->filterWhere(['almacen_id' => $idWarehouse])
                ->andFilterWhere(['like', 'LOWER(insumo.descripcion)', $descripcion])
                ->innerJoin('insumo', 'insumo.id = inventario.insumo_id')
                ->innerJoin('almacen', 'almacen.id = inventario.almacen_id')
                ->orderBy(['inventario.cantidad' => SORT_ASC])
                ->asArray()
                ->all();
        }
        $response = [
            'success' => true,
            'message' => 'Inventarios insumos',
            'records' => array_merge($queryPres, $supplies),
        ];

        return $response;
    }

    public function actionRecordsBySuppliesGroup($idWarehouse)
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $supplies = Insumo::find()
            ->select(['presentacion.*', 'unidad_medida.abreviatura as unidad_medida'])
            ->innerJoin('presentacion', 'presentacion.insumo_id = insumo.id')
            ->innerJoin('unidad_medida', 'unidad_medida.id = insumo.unidad_medida_id');
        for ($i = 0; $i < count($params); $i++) {
            $supplies = $supplies
                ->orWhere(['insumo.grupo_insumo_id' => $params[$i]]);
        }

        $presentations = $supplies
            ->asArray()
            ->all();
        $listIn = [];
        for ($i = 0; $i < count($presentations); $i++) {
            $model  = InventarioPres::find()
                ->select(['inventario_pres.*', 'presentacion.ultimo_costo', 'presentacion.costo_promedio', 'presentacion.descripcion'])
                ->where(['presentacion_id' => $presentations[$i]['id'], 'almacen_id' => $idWarehouse])
                ->innerJoin('presentacion', 'presentacion.id = inventario_pres.presentacion_id')
                ->asArray()
                ->one();
            if ($model) {
                $listIn[] = [...$model, 'unidad_medida' => $presentations[$i]['unidad_medida']];
            } else {
                $inventaryPres = new InventarioPres();
                $inventaryPres->presentacion_id = $presentations[$i]['id'];
                $inventaryPres->almacen_id = $idWarehouse;
                $inventaryPres->cantidad = 0;
                if (!$inventaryPres->save()) {
                    return $inventaryPres->errors;
                }
                $listIn[] = [...$inventaryPres, 'ultimo_costo' => $presentations[$i]['ultimo_costo'], 'descripcion' => $presentations[$i]['descripcion'], 'unidad_medida' => $presentations[$i]['unidad_medida']];
            }
        }

        $response = [
            'success' => true,
            'message' => 'Lista de insumos',
            'records' => $listIn,
        ];
        return $response;
    }

    public function actionPresentations($estado = null)
    {
        $supplies = InventarioPres::find()
            ->select(['inventario_pres.*', 'presentacion.descripcion', 'presentacion.ultimo_costo', 'unidad_medida.abreviatura as unidad_medida', 'presentacion.rendimiento', 'presentacion.insumo_id', 'insumo.inventariable', 'insumo.alerta_existencias'])
            ->innerJoin('presentacion', 'presentacion.id = inventario_pres.presentacion_id')
            ->innerJoin('insumo', 'insumo.id = presentacion.insumo_id')
            ->innerJoin('unidad_medida', 'unidad_medida.id = insumo.unidad_medida_id')
            ->filterWhere(['presentacion.estado' => $estado])
            ->asArray()
            ->all();
        $response = [
            'success' => true,
            'message' => 'Lista de presentaciones',
            'records' => $supplies,
        ];
        return $response;
    }

    public function update($params)
    {
        try {
            extract($params);
            $model = InventarioPres::find()
                ->where(['presentacion_id' => $presentacion_id, 'almacen_id' => $almacen_id])
                ->one();

            if (!$model) {
                $model = $this->create($presentacion_id, $almacen_id);
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
        $model = new InventarioPres();
        $model->almacen_id = $idWarehouse;
        $model->presentacion_id = $idSupplies;
        $model->cantidad = 0;
        return $model;
    }
}
