<?php

namespace app\controllers;

use app\models\AsignacionImpresora;
use app\models\ConfiguracionImpresora;
use app\models\Empresa;
use Yii;

class ConfiguracionImpresoraController extends \yii\web\Controller
{
    
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'index' => ['get'],
                'update' => ['POST'],
            ]
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
    public function actionIndex()
    {
        $configuration = ConfiguracionImpresora::find()->one();
        $assignedPrinters = AsignacionImpresora::find()->all();
        if ($configuration) {
            $company = Empresa::find()->one();
            $response = [
                'success' => true,
                'record' => $configuration,
                'company' => $company,
                'assignedPrinters' => $assignedPrinters
            ];
        } else {
            $response = [
                'success' => false,
                'record' => $configuration
            ];
        }
        return $response;
    }


    public function actionUpdate()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $company = ConfiguracionImpresora::find()
                                            ->one();
   
        if ($company) {
            $company->load($params, '');
            $company->save();
            $response = [
                'success' => true,
                'data' => $company,
                'message' => 'Configuración de impresora actualizada',
            ];
        } else {
            $response = [
                'success' => false,
                'data' => $company,
                'message' => 'Algo salio mal',
            ];
        }
        return $response;
    }
}
