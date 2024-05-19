<?php

namespace app\controllers;

use Yii;
use app\models\Categoria;
use app\models\Periodo;
use app\models\Producto;
use app\models\UploadForm;
use Exception;
use yii\web\UploadedFile;
use yii\data\Pagination;
use yii\helpers\Json;

class CategoriaController extends \yii\web\Controller
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

    public function actionIndex($name, $pageSize=5)
    {   
        if($name === 'undefined')$name = null;
        $query = Categoria::find()
                    ->andFilterWhere(['LIKE', 'UPPER(nombre)',  strtoupper($name)]);

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count(),
        ]);

        $categories = $query
            ->orderBy('id DESC')
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $currentPage = $pagination->getPage() + 1;
        $totalPages = $pagination->getPageCount();
        $response = [
            'success' => true,
            'message' => 'lista de categorias',
            'pageInfo' => [
                'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                'previus' => $currentPage == 1 ? null : $currentPage - 1,
                'count' => count($categories),
                'page' => $currentPage,
                'start' => $pagination->getOffset(),
                'totalPages' => $totalPages,
                'categories' => $categories
            ]
        ];
        return $response;
    }

    public function actionGetCategories () {
        $categories = Categoria::find()
                    ->where(['estado' => 'Activo'])
                      ->orderBy(['id' => 'SORT_ASC'])             
                     ->all();

        if($categories){

            $response = [
                'success' => true,
                'message' => 'Lista de categorias',
                'categories' => $categories,
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'No existen categorias',
                'categories' => [],
            ];
        }

        return $response;
    }

    public function actionCreate()
    {
   
        $category = new Categoria;
        $file = UploadedFile::getInstanceByName('file');
        $data = Json::decode(Yii::$app->request->post('data'));

        // $data = Json::decode(Yii::$app->request->post('data'));
        if($file){
            $fileName = uniqid() . '.' . $file->getExtension();
            $file->saveAs(Yii::getAlias('@app/web/upload/') . $fileName);
            $category->url_image = $fileName;
        }
        try {
        $category->load($data, '');

            if ($category->save()) {
                Yii::$app->getResponse()->setStatusCode(201);
                $response = [
                    'success' => true,
                    'message' => 'Categoria creada exitosamente',
                    'fileName' => $category
                ];
            } else {
                Yii::$app->getResponse()->setStatusCode(422,"Data Validation Failed.");
                $response = [
                    'success' => false,
                    'message' => 'Existen errores en los campos',
                    'errors' => $category->errors
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


    public function actionUpdate($idCategory)
    {
        $category = Categoria::findOne($idCategory);
        if ($category) {
            $data = JSON::decode(Yii::$app->request->post('data'));
            $category->load($data, '');

            $image = UploadedFile::getInstanceByName('file');
            if ($image) {
                $url_image = $category->url_image;
                $imageOld = Yii::getAlias('@app/web/upload/' . $url_image);
                if(file_exists($imageOld) && $url_image){
                    unlink($imageOld);
                    /* Eliminar */
                }
                $fileName = uniqid().'.'.$image->getExtension();
                $image->saveAs(Yii::getAlias('@app/web/upload/') . $fileName);
                $imageNew = Yii::getAlias('@app/web/upload/' . $fileName);
                if(file_exists($imageNew)){
                    $category -> url_image = $fileName;
                }else{
                    return $response = [
                        'success' => false,
                        'message' => 'Ocurrio un error!',
                    ];
                }
            }

            try {

                if ($category->save()) {

                    $response = [
                        'success' => true,
                        'message' => 'Categoria actualizado correctamente',
                        'category' => $category
                    ];
                } else {
                    Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed');
                    $response = [
                        'success' => false,
                        'message' => 'Existe errores en los campos',
                        'error' => $category->errors
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

    public function actionGetCategory($idCategory)
    {
        $category = Categoria::findOne($idCategory);
        if ($category) {
            $response = [
                'success' => true,
                'message' => 'Accion realizada correctamente',
                'category' => $category
            ];
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'No existe el Categoria',
                'category' => $category
            ];
        }
        return $response;
    }

    public function actionDelete($idCategory)
    {
        $category = Categoria::findOne($idCategory);

        if ($category) {
            try {
                $url_image = $category->url_image;
                $category->delete();
                $pathFile = Yii::getAlias('@webroot/upload/'.$url_image);
                if( file_exists($pathFile)){
                    unlink($pathFile);
                }
                $response = [
                    "success" => true,
                    "message" => "Categoria eliminado correctamente",
                    "category" => $category
                ];
            } catch (yii\db\IntegrityException $ie) {
                Yii::$app->getResponse()->setStatusCode(409, "");
                $response = [
                    "success" => false,
                    "message" =>  "El Categoria esta siendo usado",
                    "code" => $ie->getCode()
                ];
            } catch (\Exception $e) {
                Yii::$app->getResponse()->setStatusCode(422, "");
                $response = [
                    "success" => false,
                    "message" => $e->getMessage(),
                    "code" => $e->getCode()
                ];
            }
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                "success" => false,
                "message" => "Categoria no encontrado"
            ];
        }
        return $response;
    }
    public function actionCategories()
    {
        $categories = Categoria::find()->all();
        $response = [
            'success' => true,
            'message' => 'Lista de categorias',
            'categories' => $categories
        ];
        return $response;
    }

    public function actionGetProductsByCategory($idCategory){
        $category = Categoria::findOne($idCategory);
        if($category){
            $products = Producto::find()
                        ->where(['estado' => 'Activo', 'categoria_id' => $idCategory])
                        ->all();
            $response = [
                "success" => true,
                "message" => "Lista de productos por categoria",
                "category" => $category,
                "products" => $products
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
        $query = Categoria::find()
                    ->with(['productos' => function ($query) {
                        $query
                        ->select(['producto.id', 'producto.categoria_id','producto.nombre', 'producto.url_image', 'producto.precio_venta', 'producto.estado',  'producto.stock', 'producto.tipo' , 'producto.id as producto_id', 'producto.stock_active'])
                        ->andWhere(['estado' => 'Activo'])
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
    public function actionDisableCategory( $idCategory ){
        $category = Categoria::findOne($idCategory);
        if($category){
            $category -> estado = 'Inactivo';
            if($category -> save()){
                $response = [
                    'success' => true,
                    'message' => 'Producto actualizado'
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

    public function actionExistsPeriod(){
        $period = Periodo::find()
                        ->where(['estado' => true])
                        ->one();
        if($period){
                $response = [
                    'success' => true, 
                    'message' => 'existe periodo activo',
                ];
        }else{
            $response = [
                'success' => false, 
                'message' => 'En este instante está fuera de los horarios de atención',
            ];
        }
        return $response;
    }

}
