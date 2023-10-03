<?php

namespace app\controllers;

use app\models\Mesa;
use app\models\Salon;
use Yii;
use yii\helpers\Json;
use yii\web\UploadedFile;

class SalonController extends \yii\web\Controller
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


    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionCreateLounge(){
        $lounge = new Salon();
        $data = Json::decode(Yii::$app->request->post('data'));
        $lounge -> load($data, '');
        $file = UploadedFile::getInstanceByName('file');
        if($file){
            $fileName = uniqid().'.'.$file -> getExtension();
            $file -> saveAs(Yii::getAlias('@app/web/upload/').$fileName);
            $lounge -> url_image = $fileName;
        }

        if($lounge -> save()){
            /* Creamos los espacios segun el nrofilas y columnas */
            $nroTables = $data['nro_filas']* $data['nro_columnas'];
            for($i = 0; $i < $nroTables; $i++){
                $table = new Mesa();
                $table -> salon_id = $lounge -> id;
                $table -> nombre = strval( $i + 1);
                if(!$table -> save()){
                    return  [
                        'success' => false,
                        'message' => 'Existen errores en los campos',
                        'errors' => $table->errors
                    ];
                }
            }
            $response = [
                'success' => true,
                'message' => 'Salon creado exitosamente',
                'lounge' => $lounge
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'Existen errores en los campos',
                'errors' => $lounge->errors
            ];
        }
        return $response;
    }

    public function actionGetLounges ($estado = null){
        
        $lounges = Salon::find()
                        ->filterWhere(['estado' => $estado])
                        ->all();
        if($lounges){
            $response = [
                'success' => true,
                'message' => 'Lista de salones',
                'lounges' => $lounges
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'Existen errores en los campos',
                'errors' => []
            ];
        }
        return $response;
    }

    public function actionGetLounge ($idLounge){
        $lounge =  Salon::findOne($idLounge);
        if($lounge){
            $response = [
                'success' => true,
                'message' => 'Salon',
                'lounge' => $lounge
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'No existe el salon',
                'errors' => []
            ];
        }
        return $response;
    }
    public function actionUpdate($idLounge){
        $lounge = Salon::findOne($idLounge);
        if($lounge){
            $data = JSON::decode(Yii::$app->request->post('data'));
            $lounge->load($data, '');

            $image = UploadedFile::getInstanceByName('file');
            if ($image) {
                $url_image = $lounge->url_image;
                $imageOld = Yii::getAlias('@app/web/upload/' . $url_image);
                if(file_exists($imageOld) && $url_image){
                    unlink($imageOld);
                    /* Eliminar */
                }
                $fileName = uniqid().'.'.$image->getExtension();
                $image->saveAs(Yii::getAlias('@app/web/upload/') . $fileName);
                $imageNew = Yii::getAlias('@app/web/upload/' . $fileName);
                if(file_exists($imageNew)){
                    $lounge -> url_image = $fileName;
                }else{
                    return $response = [
                        'success' => false,
                        'message' => 'Ocurrio un error!',
                    ];
                }
                /* Si existe ya una imagen, borrarl y cargar la nueva */
            }

            if($lounge -> save()){
                $response = [
                    'success' => true,
                    'message' => 'Actualizado exitosamente',
                ];
            }else{
                $response = [
                    'success' => false,
                    'message' => 'Existe errores en los campos',
                    'error' => $lounge->errors
                ];
            }
        }else{
              $response = [
                'success' => false,
                'message' => 'No se encontro el salon',
                'lounge' => []
            ];
        }
        return $response;
    }
}
