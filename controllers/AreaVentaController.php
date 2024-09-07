<?php

namespace app\controllers;

use app\models\AreaVenta;
use app\models\Mesa;
use Exception;
use Yii;
use yii\web\UploadedFile;
use yii\helpers\Json;

class AreaVentaController extends \yii\web\Controller
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


    public function actionIndex($estado = null, $type = null)
    {
        $query = AreaVenta::find()
            ->select([
                '*',
                new \yii\db\Expression('EXISTS (SELECT 1 FROM mesa WHERE mesa.area_venta_id = area_venta.id) AS hasTables')
            ])
            ->filterWhere(['tipo' => $type])
            ->andFilterWhere(['estado' => $estado])
            ->orderBy(['id' => SORT_DESC])
            ->asArray()
            ->all();

        $response = [
            'success' => true,
            'message' => 'Lista de areas de venta',
            'records' => $query
        ];

        return $response;
    }

    public function actionCreate()
    {
        $lounge = new AreaVenta();
        $data = Json::decode(Yii::$app->request->post('data'));
        $lounge->load($data, '');
        $file = UploadedFile::getInstanceByName('file');

        if ($file) {
            $fileName = uniqid() . '.' . $file->getExtension();
            $file->saveAs(Yii::getAlias('@app/web/upload/') . $fileName);
            $lounge->url_image = $fileName;
        }
        if ($lounge->save()) {
            $response = [
                'success' => true,
                'message' => 'Salon creado exitosamente',
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'No se pudo crear el area de venta',
                'errors' => $lounge->errors
            ];
        }
        return $response;
    }
    public function actionGetLounge($idLounge)
    {
        $lounge =  AreaVenta::findOne($idLounge);
        if ($lounge) {
            $response = [
                'success' => true,
                'message' => 'Salon',
                'lounge' => $lounge
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'No existe el salon',
                'errors' => []
            ];
        }
        return $response;
    }
    public function actionUpdate($idArea)
    {
        $saleArea = AreaVenta::findOne($idArea);
        if ($saleArea) {
            $data = JSON::decode(Yii::$app->request->post('data'));
            $saleArea->load($data, '');

            $image = UploadedFile::getInstanceByName('file');
            if ($image) {
                $url_image = $saleArea->url_image;
                $imageOld = Yii::getAlias('@app/web/upload/' . $url_image);
                if (file_exists($imageOld) && $url_image) {
                    unlink($imageOld);
                    /* Eliminar */
                }
                $fileName = uniqid() . '.' . $image->getExtension();
                $image->saveAs(Yii::getAlias('@app/web/upload/') . $fileName);
                $imageNew = Yii::getAlias('@app/web/upload/' . $fileName);
                if (file_exists($imageNew)) {
                    $saleArea->url_image = $fileName;
                } else {
                    return $response = [
                        'success' => false,
                        'message' => 'Ocurrio un error!',
                    ];
                }
            }

            if ($saleArea->save()) {
                /* Actualizamos las mesas */
                if (!empty($data['hastables'])) {
                    $nroTables = $data['nro_filas'] * $data['nro_columnas'];
                    $lastTable = Mesa::find()->orderBy(['id' => SORT_DESC])->one();
                    for ($i = 1; $i <= $nroTables; $i++) {
                        $table = new Mesa();
                        $table->area_venta_id = $saleArea->id;
                        $table->nombre = strval($lastTable->id + $i);
                        $table->tipo = 'SHAPE1';
                        if (!$table->save()) {
                            return  [
                                'success' => false,
                                'message' => 'Existen errores en los campos',
                                'errors' => $table->errors
                            ];
                        }
                    }
                }
                $response = [
                    'success' => true,
                    'message' => 'Actualizado exitosamente',
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'No se encontro el salon',
                'lounge' => []
            ];
        }
        return $response;
    }

    public function actionDelete($id)
    {
        $saleArea = AreaVenta::findOne($id);
        if ($saleArea) {

            try {
                if ($saleArea->delete()) {
                    /* Eliminar imagen si existe */
                    $url_image = $saleArea->url_image;
                    $imageOld = Yii::getAlias('@app/web/upload/' . $url_image);
                    if (file_exists($imageOld) && $url_image) {
                        unlink($imageOld);
                        /* Eliminar */
                    }

                    $response = [
                        'success' => true,
                        'message' => 'Area de venta eliminado correctamente',
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
