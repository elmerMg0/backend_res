<?php

namespace app\controllers;

use app\models\CategoriaGasto;
use Exception;
use Yii;

class CategoriaGastoController extends \yii\web\Controller
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
    public function actionIndex($estado = null)
    {
        $records = CategoriaGasto::find()
            ->filterWhere(['estado' => $estado])
            ->orderBy(['id' => 'DESC'])
            ->all();

        $response = [
            'success' => true,
            'message' => 'Lista de categorias de gastos',
            'records' => $records
        ];

        return $response;;
    }

    public function actionCreate(){
        $params = Yii::$app->getRequest()->getBodyParams();
        $paymentType = new CategoriaGasto();
        $paymentType -> load($params, '');

        if($paymentType->save()){
            $response = [
                'success' => true,
                'message' => 'Tipo de gasto creado con exito',
                'records' => $paymentType
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'Tipo de gasto no creado',
                'errors' => $paymentType -> errors
            ];
        }
        return $response;
    }

    public function actionUpdate($id){
        $params = Yii::$app->getRequest()->getBodyParams();
        $paymentType = CategoriaGasto::findOne($id);
        $paymentType -> load($params, '');
        if($paymentType->save()){
            $response = [
                'success' => true,
                'message' => 'Tipo de gasto actualizado con exito',
                'records' => $paymentType
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'Tipo de gasto no actualizado',
            ];
        }
        return $response;
    }

    public function actionDelete($id)
    {
        $paymentType = CategoriaGasto::findOne($id);
        if ($paymentType) {

            try{
                if ($paymentType->delete()) {
                    $response = [
                        'success' => true,
                        'message' => 'Tipo de gasto eliminado correctamente',
                    ];
                }
            }catch(Exception $e){
                $response = [
                    'success' => false,
                    'message' => 'No se puede eliminar el Tipo de gasto porque tiene registros relacionados'
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
