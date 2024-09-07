<?php

namespace app\controllers;

use app\models\AsignacionAreaAlmacen;
use app\models\Inventario;
use Exception;
use Yii;

class AsignacionAreaAlmacenController extends \yii\web\Controller
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

    public function actionGetAssignedByRecipe($idRecipe)
    {
        $records = AsignacionAreaAlmacen::find()
            ->where(['receta_id' => $idRecipe])
            ->all();
        $response = [
            'success' => true,
            'message' => '',
            'records' => $records
        ];
        return $response;
    }

    public function actionUpdate($idRecipe)
    {
        //si ya existe update si no existe create
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $params = Yii::$app->getRequest()->getBodyParams();

            /* Crear nuevas asignaciones */
            for ($i = 0; $i < count($params); $i++) {
                $model = AsignacionAreaAlmacen::find()->where(['receta_id' => $idRecipe, 'area_venta_id' => $params[$i]['area_venta_id']])->one();
                if (!$model) {
                    $model = new AsignacionAreaAlmacen();
                }
                $model->load($params[$i], '');
                if (!$model->save()) {
                    throw new Exception(strval($model->getErrors()));
                }
            }

            /* Crear registro inventario segun a insumo_id y almacen_id */
            for ($i = 0; $i < count($params); $i++) {
                $model = Inventario::find()->where(['insumo_id' => $params[$i]['insumo_id'], 'almacen_id' => $params[$i]['almacen_id']])->one();
                if (!$model) {
                    $model = new Inventario();
                    $model->cantidad = 0;
                }
                $model->load($params[$i], '');
                if (!$model->save()) {
                    throw new Exception(strval($model->getErrors()));
                }
            }

            $transaction->commit();
            $response = [
                'success' => true,
                'message' => 'Se guardaron los cambios',
            ];
        } catch (Exception $e) {
            $transaction->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        return $response;
    }
}
