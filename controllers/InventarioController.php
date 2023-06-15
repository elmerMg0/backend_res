<?php

namespace app\controllers;

use app\models\Inventario;
use app\models\Producto;
use Yii;
use yii\data\Pagination;

class InventarioController extends \yii\web\Controller
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

    public function actionIndex($pageSize=10)
    {
        $query = Inventario::find() 
                            ->with('producto');

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count()
        ]
        );

        $inventaries = $query 
                        ->offset($pagination -> offset)
                        -> limit($pagination -> limit)
                        ->asArray()
                        ->all();
        $currentPage = $pagination->getPage() + 1;
        $totalPages = $pagination->getPageCount();

        $response = [
            'success' => true,
            'inventaries' => $inventaries,
            'pageInfo' => [
                'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                'previus' => $currentPage == 1 ? null : $currentPage - 1,
                'count' => count($inventaries),
                'page' => $currentPage,
                'start' => $pagination->getOffset(),
                'totalPages' => $totalPages,
            ],
        ];

        return $response; 
    }

    public function actionCreate (){
        $params = Yii::$app->getRequest() -> getBodyParams();

        $inventary = new Inventario();
        $inventary -> load($params, '');
        date_default_timezone_set('America/La_Paz');
        $inventary -> fecha = date('Y-m-d H:i:s');

        $product = Producto::findOne($params['producto_id']);
        $product -> stock = $params['total'];

        if($inventary -> save() && $product -> save()){
            $response = [
                'success' => true,
                'message' => 'Periodo iniciado con exito!',
                'period' => $inventary
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'Existen parametros incorrectos',
                'errors' => $inventary->errors
            ];
        }
        return $response;
    }

}
