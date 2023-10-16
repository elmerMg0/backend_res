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

    public function actionIndex($pageSize=5)
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
                        ->orderBy(['id' => SORT_DESC])
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
        $inventaries = Yii::$app->getRequest() -> getBodyParams();
     /*    $inventaries = $params['productsToEdit']; */

        if($inventaries){
            for ($i=0; $i < count($inventaries); $i++) { 
                $inventary = $inventaries[$i];
                $inventaryNew = new Inventario();

                date_default_timezone_set('America/La_Paz');
                $inventaryNew -> fecha = date('Y-m-d H:i:s');
                $inventaryNew -> producto_id = $inventary['id'] ;
                $inventaryNew -> stock = $inventary['stock'] ;
                $inventaryNew -> nuevo_stock = $inventary['newStock'] ;
                $inventaryNew -> total = $inventary['stock'] + $inventary['newStock'] ;
                $inventaryNew -> last_one = true ;

                $inventaryOld = Inventario::find() 
                                    ->where(['producto_id' => $inventary['id'], 'last_one' => true])
                                    ->one();
                if($inventaryOld){
                    $inventaryOld -> last_one = false;
                    $inventaryOld -> save();
                }

                $product = Producto::findOne($inventary['id']);
                $product -> stock = $inventary['stock'] + $inventary['newStock'];
                
                if($inventaryNew -> save() && $product -> save()){
                $response = [
                    'success' => true,
                    'message' => 'Periodo iniciado con exito!',
                    'period' => $inventaryNew
                ];
                }else{
                    $response = [
                        'success' => false,
                        'message' => 'Existen parametros incorrectos',
                        'errors' => $inventaryNew->errors
                    ];
                }
             }
        }else{
            $response = [
                'succes' => false,
                'message' => 'No existen inventarios'
            ];
        }
        return $response;
    }

    public function actionGetCurrentInventary ($pageSize=10){
        $query = Inventario::find() 
                        ->select(['producto.nombre', 'producto.stock','fecha', 'total', 'nuevo_stock', 'precio_venta', 'precio_compra'])
                        ->innerJoin('producto', 'producto.id = inventario.producto_id')
                        ->where(['inventario.last_one' => true]);

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count()
            ]
        );

        $inventaries = $query 
            ->offset($pagination -> offset)
            -> limit($pagination -> limit)
            ->orderBy(['producto.stock' => SORT_ASC])
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

}
