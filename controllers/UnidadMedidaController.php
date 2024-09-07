<?php

namespace app\controllers;

use app\models\UnidadMedida;
use Exception;
use Yii;
use yii\data\Pagination;

class UnidadMedidaController extends \yii\web\Controller
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

    public function actionIndex($description, $pageSize=5)
    {   
        $description = isset($description) ? $description : null;
        $query = UnidadMedida::find()
                    ->select(['insumo.id', 'insumo.grupo_insumo_id' ,'insumo.descripcion', 'insumo.estado', 'grupo_insumo.descripcion as grupo_insumo'])
                    ->innerJoin('grupo_insumo', 'grupo_insumo.id = insumo.grupo_insumo_id')
                    ->andFilterWhere(['LIKE', 'UPPER(insumo.descripcion)',  strtoupper($description)]);

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count(),
        ]);

        $records = $query
            ->orderBy('id DESC')
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->asArray()
            ->all();

        $currentPage = $pagination->getPage() + 1;
        $totalPages = $pagination->getPageCount();
        $response = [
            'success' => true,
            'message' => 'lista de unidades de medida',
            'pageInfo' => [
                'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                'previus' => $currentPage == 1 ? null : $currentPage - 1,
                'count' => count($records),
                'page' => $currentPage,
                'start' => $pagination->getOffset(),
                'totalPages' => $totalPages,
            ],
            'records' => $records
        ];
        return $response;
    }

    public function actionGetUnitsOfMeasure () {
        $records = UnidadMedida::find()
                      ->orderBy(['id' => 'SORT_ASC'])             
                      ->all();

        if($records){
            $response = [
                'success' => true,
                'message' => 'Lista de unidades de medida',
                'records' => $records,
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'No existen unidades de medida',
                'records' => [],
            ];
        }
        return $response;
    }

    public function actionCreate()
    {
        $supplies = new UnidadMedida();
        $params = Yii::$app->getRequest()->getBodyParams();
      
        try {
        $supplies->load($params, '');

            if ($supplies->save()) {
                Yii::$app->getResponse()->setStatusCode(201);
                $response = [
                    'success' => true,
                    'message' => 'Insumo creado exitosamente',
                    'fileName' => $supplies
                ];
            } else {
                Yii::$app->getResponse()->setStatusCode(422,"Data Validation Failed.");
                $response = [
                    'success' => false,
                    'message' => 'Existen errores en los campos',
                    'errors' => $supplies->errors
                ];
            }
        } catch (Exception $e) {
            Yii::$app->getResponse()->setStatusCode(500);
            $response = [
                'success' => false,
                'message' => 'ocurrio un error',
                'fileName' => $e->getMessage()
            ];
        }

        return $response;
    }


    public function actionUpdate($idsupplies)
    {
        $supplies = UnidadMedida::findOne($idsupplies);
        if ($supplies) {
            $data = Yii::$app->getRequest()->getBodyParams();
            $supplies->load($data, '');
            try {
                if ($supplies->save()) {
                    $response = [
                        'success' => true,
                        'message' => 'unidades de medida actualizado correctamente',
                        'supplies' => $supplies
                    ];
                } else {
                    Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed');
                    $response = [
                        'success' => false,
                        'message' => 'Existe errores en los campos',
                        'error' => $supplies->errors
                    ];
                }
            } catch (Exception $e) {
                Yii::$app->getResponse()->setStatusCode(500);
                $response = [
                    'success' => false,
                    'message' => 'Categoria no encontrado',
                    'error' => $e->getMessage()
                ];
            }
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'Categoria no encontrado',
            ];
        }
        return $response;
    }

    public function actionGetCategory($idsupplies)
    {
        $supplies = UnidadMedida::findOne($idsupplies);
        if ($supplies) {
            $response = [
                'success' => true,
                'message' => 'Insumo actualizado correctamente',
                'su$supplies' => $supplies
            ];
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'No existe el Insumo',
                'su$supplies' => $supplies
            ];
        }
        return $response;
    }

    public function actionCategories()
    {
        $suppliesGroup = UnidadMedida::find()->all();
        $response = [
            'success' => true,
            'message' => 'Lista de unidades de medida',
            'suppliesGroup' => $suppliesGroup
        ];
        return $response;
    }

    public function actionGetProductsByCategory($idCategory){
        $category = UnidadMedida::findOne($idCategory);
        if($category){
           /*  $products = UnidadMedida::find()
                        ->where(['estado' => 'Activo', 'categoria_id' => $idCategory])
                        ->all(); */
            $response = [
                "success" => true,
                "message" => "Lista de productos por categoria",
                "category" => $category,
                //"products" => $products
            ];
        }else{
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                "success" => false,
                "message" => "Categoria no encontrada",
            ];
        }
        return $response;
    }

    public function actionGetCategoryWithProducts(){
        $query = UnidadMedida::find()
                    ->with(['productos' => function ($query) {
                        $query
                        ->select(['producto.id', 'producto.categoria_id','producto.nombre', 'producto.url_image', 'producto.precio_venta', 'producto.estado',  'producto.stock', 'producto.tipo' , 'producto.id as producto_id', 'producto.stock_active'])
                        ->andWhere(['estado' => true])
                        ->orderBy(['id' => 'SORT_ASC']);
                    }])
                    ->orderBy(['id' => 'SORT_ASC'])
                    ->asArray()
                    ->all();
        if($query){
            $response = [
                'success' => true,
                'message' => 'Lista de categorias',
                'categories' => $query
            ];
        }else{
            $response = [
                'success' => true,
                'message' => 'Lista de categorias',
                'categories' => []
            ];
        }
        return $response;
    }
    public function actionDisable( $idsupplies ){
        $supplies = UnidadMedida::findOne($idsupplies);
        if($supplies){
            $supplies -> estado = false;
            if($supplies -> save()){
                $response = [
                    'success' => true,
                    'message' => 'Insumo actualizado'
                ];
            }else{
                $response = [
                    'success' => false,
                    'message' => 'Ocurrio un error!'
                ];
            }
        }else{
            $response = [
                'success' => false,
                'message' => 'Ocurrio un error!'
            ];
        }
        return $response;
    }

}
