<?php

namespace app\controllers;

use app\models\GrupoTipoPago;
use app\models\TipoPago;
use Exception;
use Yii;

class TipoPagoController extends \yii\web\Controller
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

    public function actionGroups(){
        $groups = GrupoTipoPago::find()->all();

        $response = [
            'success' => true,
            'message' => 'Listado de grupos de tipo de pago',
            "records" => $groups
        ];

        return $response;
    }

    public function actionIndex($estado = null){
        $types = TipoPago::find()
                            ->select(['tipo_pago.*', 'grupo_tipo_pago.descripcion as grupo_tipo'])
                            ->innerJoin('grupo_tipo_pago', 'grupo_tipo_pago.id = tipo_pago.grupo_tipo_pago_id')
                            ->filterWhere(['estado' => $estado])
                            ->orderBy(['id' => SORT_DESC])
                            ->asArray()
                            ->all();
        $response = [
            'success' => true,
            'message' => 'Listado de tipos de pago',
            "records" => $types
        ];
        return $response;
    }


    public function actionCreate(){
        $params = Yii::$app->getRequest()->getBodyParams();
        $paymentType = new TipoPago();
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
        $paymentType = TipoPago::findOne($id);
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
        $paymentType = TipoPago::findOne($id);
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
