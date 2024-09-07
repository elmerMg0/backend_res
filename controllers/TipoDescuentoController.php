<?php

namespace app\controllers;

use app\models\TipoDescuento;
use Exception;
use Yii;

class TipoDescuentoController extends \yii\web\Controller
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

    public function actionIndex($estado = null){
        $types = TipoDescuento::find()
                            ->filterWhere(['estado' => $estado])
                            ->orderBy(['id' => SORT_DESC])
                            ->all();
        $response = [
            'success' => true,
            'message' => 'Tipos de descuentos',
            "records" => $types
        ];
        return $response;
    }


    public function actionCreate(){
        $params = Yii::$app->getRequest()->getBodyParams();
        $paymentType = new TipoDescuento();
        $paymentType -> load($params, '');

        if($paymentType->save()){
            $response = [
                'success' => true,
                'message' => 'Tipo de pago creado con exito',
                'records' => $paymentType
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'Tipo de pago no creada',
                'errors' => $paymentType -> errors
            ];
        }
        return $response;
    }

    public function actionUpdate($id){
        $params = Yii::$app->getRequest()->getBodyParams();
        $paymentType = TipoDescuento::findOne($id);
        $paymentType -> load($params, '');
        if($paymentType->save()){
            $response = [
                'success' => true,
                'message' => 'Tipo de pago actualizada con exito',
                'records' => $paymentType
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'Tipo de pago no actualizado',
            ];
        }
        return $response;
    }

    public function actionDelete($id)
    {
        $paymentType = TipoDescuento::findOne($id);
        if ($paymentType) {

            try{
                if ($paymentType->delete()) {
                    $response = [
                        'success' => true,
                        'message' => 'Tipo de pago eliminado correctamente',
                    ];
                }
            }catch(Exception $e){
                $response = [
                    'success' => false,
                    'message' => 'No se puede eliminar el Tipo de pago porque tiene registros relacionados'
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
