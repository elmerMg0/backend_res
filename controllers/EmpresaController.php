<?php

namespace app\controllers;

use app\models\Empresa;
use yii\helpers\Json;
use Yii;
use Exception;
use yii\web\UploadedFile;

class EmpresaController extends \yii\web\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'login' => ['POST'],
                'create-user' => ['POST'],
                'update' => ['POST']

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
    public function actionGetCompany()
    {
        $company = Empresa::find()->all();
        if ($company) {
            $response = [
                'success' => true,
                'data' => $company[0]
            ];
        } else {
            $response = [
                'success' => false,
                'data' => $company
            ];
        }
        return $response;
    }


    public function actionUpdate($id)
    {
        /* Ver si existe */
        $company = Empresa::findOne($id);
        $params = Json::decode(Yii::$app->request->post('body'));
        if (!$company) {
            $company = new Empresa();
            //actializar
        }
        $company->load($params, '');
        $image = UploadedFile::getInstanceByName('image');
        if ($image) {
            $image_url = $company->image_url;
            $imageOld = Yii::getAlias('@app/web/upload/' . $image_url);
            if(file_exists($imageOld)){
                unlink($imageOld);
                /* Eliminar */
            }
            
            $fileName = uniqid().'.'.$image->getExtension();
            $image->saveAs(Yii::getAlias('@app/web/upload/') . $fileName);
            $imageNew = Yii::getAlias('@app/web/upload/' . $fileName);
            if(file_exists($imageNew)){
                $company -> image_url = $fileName;
            }else{
                return $response = [
                    'success' => false,
                    'message' => 'Ocurrio un error!',
                ];
            }
            /* Si existe ya una imagen, borrarl y cargar la nueva */
        }
        try {
            if ($company->save()) {
                $response = [
                    'success' => true,
                    'message' => 'Se actualizo de manera correcta'
                ];
            } else {
                Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed.');
                $response = [
                    'success' => false,
                    'message' => 'Existen campos incorrectos',
                    'data' => $company->errors
                ];
            }
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => 'Error al Actualizar',
                'data' => $e->getMessage()
            ];
        }
        return $response;
    }

    public function actionIndex()
    {
        return $this->render('index');
    }
}
