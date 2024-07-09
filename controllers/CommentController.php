<?php

namespace app\controllers;

use app\models\Comentario;
use Yii;

class CommentController extends \yii\web\Controller
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

    public function actionIndex($idProduct)
    {
        $idProduct = $idProduct ? $idProduct : null;
        $comments = Comentario::find() 
                                -> where(['producto_id' => $idProduct])
                                -> orderBy(['id' => SORT_DESC])
                                ->all();
        $response = [
            'success' => true,
            'message' => 'Lista de comentarios',
            'comments' => $comments
        ];

        return $response;
    }

    public function actionCreate()
    {
        $model = new Comentario();
        $model->load(Yii::$app->request->post(), '');
        if($model->save()){
            $response = [
                'success' => true,
                'message' => 'Comentario creado exitosamente',
                'comment' => $model
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'No se pudo crear el comentario',
                'errors' => $model->errors()
            ];
        }
        return $response;
    }
    public function actionUpdate($idComment)
    {
        $model = Comentario::findOne($idComment);
        if($model){
            $params = Yii::$app->request->getBodyParams();
            $model->load($params, '');
            if($model->save()){
                $response = [
                    'success' => true,
                    'message' => 'Comentario actualizado exitosamente',
                    'comment' => $model
                ];
            }else{
                $response = [
                    'success' => false,
                    'message' => 'No se pudo actualizar el comentario',
                    'errors' => $model->errors()
                ];
            }
        }else{
            $response = [
                'success' => false,
                'message' => 'No se encontro el comentario' 
            ];
        }   
        return $response;
    }

    public function actionDelete ($idComment)
    {
        $model = Comentario::findOne($idComment);
        if($model){
            $model->delete();
            $response = [
                'success' => true,
                'message' => 'Comentario eliminado exitosamente',
                'comment' => $model
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'No se encontro el comentario' 
            ];
        }   
        return $response;
    }
}
