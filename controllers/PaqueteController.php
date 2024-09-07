<?php

namespace app\controllers;

use app\models\Paquete;
use Exception;
use Yii;

class PaqueteController extends \yii\web\Controller
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

    public function actionPackages($idProduct){
        $packages = Paquete::find()
            ->select(['paquete.*', 'producto.nombre'])
            ->where(['producto_parent_id' => $idProduct])
            ->innerJoin('producto', 'producto.id=paquete.producto_id')
            ->asArray()
            ->all();

        $response = [
            'success' => true,
            'message' => 'Lista de paquetes',
            'records' => $packages
        ];

        return $response;
    }

    public function actionUpdate(){
        $params = Yii::$app->getRequest()->getBodyParams();
        extract($params);
        $transaction = Yii::$app->db->beginTransaction();
        try{
            foreach ($productList as $key => $value) {
                $paquete = new Paquete();
                if(isset($value['id'])){
                    $paquete = Paquete::findOne($value['id']);
                }
                $paquete->load($value, '');
                if(!$paquete->save()){
                    throw new Exception(json_encode($paquete->errors));
                }
            }
            foreach ($itemsToDelete as $key => $value) {
                $paquete = Paquete::findOne($value);
                if(!$paquete->delete()){
                    throw new Exception(json_encode($paquete->errors));
                }
            }
            $transaction->commit();
        }catch(Exception $e){
            $transaction->rollBack();
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }

        $response = [
            'success' => true,
            'message' => 'Paquete actualizado',
        ];

        return $response;
    }
}
