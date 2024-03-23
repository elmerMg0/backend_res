<?php

namespace app\controllers;

use Yii;
use app\models\Producto;
use app\models\Categoria;
use app\models\CategoriaGasto;
use app\models\Gasto;
use app\models\SubProducto;
use Exception;
use yii\web\UploadedFile;
use yii\helpers\Json;
use yii\data\Pagination;

class ProductoController extends \yii\web\Controller
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
                'get-product' => ['get'],
                'products' => ['post'],
                'disable-product' => ['get']
            ]
        ];
        // add Bearer authentication filter     	
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['options']
        ];

        /*$behaviors['access'] = [
            'class' => \yii\filters\AccessControl::class,
            'only' => ['get-product', 'products'], // acciones a las que se aplicará el control
            'except' => [],    // acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['get-product', 'products'], // acciones que siguen esta regla
                    'roles' => ['mesero'] // control por roles  permisos
                ],
                 [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['index','update','delete','create'], // acciones que siguen esta regla
                    'roles' => ['administrador'] // control por roles  permisos
                ], 
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => [''], // acciones que siguen esta regla
                    'matchCallback' => function ($rule, $action) {
                        // control personalizado
                        return true;
                    }
                ],
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => [''], // acciones que siguen esta regla
                    'matchCallback' => function ($rule, $action) {
                        // control personalizado equivalente a '@’ de usuario 
                        // autenticado
                        return Yii::$app->user->identity ? true : false;
                    }
                ],
                //…
	    ],
	];*/

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

    public function actionIndex($name, $pageSize = 5)
    {
        if($name === 'undefined')$name = null;
        $query = Producto::find()
                    ->select(['producto.*', 'categoria.nombre As nombre_categoria'])
                    ->join('LEFT JOIN', 'categoria', 'categoria.id = producto.categoria_id')
                    ->andFilterWhere(['LIKE', 'UPPER(producto.nombre)',  strtoupper($name)]);

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count(),
        ]);

        $products = $query
            ->orderBy('id DESC')
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->asArray()
            ->all();

        $currentPage = $pagination->getPage() + 1;
        $totalPages = $pagination->getPageCount();
        $response = [
            'success' => true,
            'message' => 'lista de productos',
            'pageInfo' => [
                'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                'previus' => $currentPage == 1 ? null : $currentPage - 1,
                'count' => count($products),
                'page' => $currentPage,
                'start' => $pagination->getOffset(),
                'totalPages' => $totalPages,
            ],
            'products' => $products
        ];
        return $response;
    }

    public function actionCreate($idCategory)
    {

        $category = Categoria::findOne($idCategory);
        if ($category) {

            $product = new Producto();
            $file = UploadedFile::getInstanceByName('file');
            $data = Json::decode(Yii::$app->request->post('data'));
            //  $varieties = Json::decode(Yii::$app->request->post('varieties'));
            if ($file) {
                $fileName = uniqid() . '.' . $file->getExtension();
                $file->saveAs(Yii::getAlias('@app/web/upload/') . $fileName);
                $product->url_image = $fileName;
            }
            $db = Yii::$app->db;
            $transaction = $db->beginTransaction();

            try {
                $product->load($data, '');
                $categoryGasto = CategoriaGasto::find()->where(['nombre' => 'Costo de Ventas']) -> one();
                if($categoryGasto){
                    $expense = new Gasto();
                    $expense->nombre = $product->nombre;
                    $expense -> categoria_gasto_id = $categoryGasto -> id;
                    $expense->save();
                }

                if ($product->save()) {
                    Yii::$app->getResponse()->setStatusCode(201);
                    /* if($varieties){
                        for($i = 0; $i < count($varieties); $i ++ ){
                            $variety = $varieties[$i];
                            $newVariety = new SubProducto();
                            $newVariety -> nombre = $variety[1];
                            $newVariety -> producto_id = $product->id;
                            if($newVariety -> save()){

                            }else{
                                return [
                                    'success' => false,
                                    'message' => 'Existen errores en los campos',
                                    'fileName' => $newVariety -> errors
                                ];
                            }
                        }
                    } */
                    $transaction -> commit();
                    $response = [
                        'success' => true,
                        'message' => 'Producto creado exitosamente',
                        'fileName' => $product
                    ];
                } else {
                    Yii::$app->getResponse()->setStatusCode(422, "Data Validation Failed.");
                    $response = [
                        'success' => false,
                        'message' => 'Existen errores en los campos',
                        'errors' => $product->errors
                    ];
                }
            } catch (Exception $e) {
                $transaction->rollBack();
                Yii::$app->getResponse()->setStatusCode(500);
                $response = [
                    'success' => false,
                    'message' => 'ocurrio un error',
                    'fileName' => $e->getMessage()
                ];
            }
        }else{
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'Categoria no encontrada',
            ];
        }

        return $response;
    }


    public function actionUpdate($idProduct)
    {
        $product = Producto::findOne($idProduct);
        if ($product) {
            $data = JSON::decode(Yii::$app->request->post('data'));
            $varieties = Json::decode(Yii::$app->request->post('varieties'));

            $product->load($data, '');

            $image = UploadedFile::getInstanceByName('file');
            if ($image) {
                $url_image = $product->url_image;
                $imageOld = Yii::getAlias('@app/web/upload/' . $url_image);
                if(file_exists($imageOld) && $url_image){
                    unlink($imageOld);
                    /* Eliminar */
                }
                $fileName = uniqid().'.'.$image->getExtension();
                $image->saveAs(Yii::getAlias('@app/web/upload/') . $fileName);
                $imageNew = Yii::getAlias('@app/web/upload/' . $fileName);
                if(file_exists($imageNew)){
                    $product -> url_image = $fileName;
                }else{
                    return $response = [
                        'success' => false,
                        'message' => 'Ocurrio un error!',
                    ];
                }
                /* Si existe ya una imagen, borrarl y cargar la nueva */
            }
            try {
                if ($product->save()) {
                    if($varieties){
                    
                        for($i = 0; $i < count($varieties); $i ++ ){
                            $variety = $varieties[$i];
                            $newVariety = SubProducto::find()->where(['producto_id' => $product->id]);
                            $newVariety -> nombre = $variety[1];
                            $newVariety -> producto_id = $product->id;
                            if($newVariety -> save()){

                            }else{
                                return [
                                    'success' => false,
                                    'message' => 'Existen errores en los campos',
                                    'fileName' => $newVariety -> errors
                                ];
                            }
                        }
                    };
                    $response = [
                        'success' => true,
                        'message' => 'Producto actualizado correctamente',
                        'product' => $product
                    ];
                } else {
                    Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed');
                    $response = [
                        'success' => false,
                        'message' => 'Existe errores en los campos',
                        'error' => $product->errors
                    ];
                }
            } catch (Exception $e) {
                Yii::$app->getResponse()->setStatusCode(500);
                $response = [
                    'success' => false,
                    'message' => 'Producto no encontrado',
                    'error' => $e->getMessage()
                ];
            }
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'Producto no encontrado',
            ];
        }
        return $response;
    }

    public function actionGetProduct($idProduct)
    {
        $product = Producto::findOne($idProduct);
        if ($product) {
            $response = [
                'success' => true,
                'message' => 'Accion realizada correctamente',
                'product' => $product
            ];
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'No existe el Categoria',
                'product' => $product
            ];
        }
        return $response;
    }

    public function actionDelete($idProduct)
    {
        $product = Producto::findOne($idProduct);

        if ($product) {
            try {
                $url_image = $product->url_image;
                $product->delete();
                /* Si existe imagen elimminar */
                $pathFile = Yii::getAlias('@webroot/upload/' . $url_image);
                if( file_exists($pathFile)){
                    unlink($pathFile);
                }
                $response = [
                    "success" => true,
                    "message" => "Producto eliminado correctamente",
                    "product" => $product
                ];
            } catch (yii\db\IntegrityException $ie) {
                Yii::$app->getResponse()->setStatusCode(409, "");
                $response = [
                    "success" => false,
                    "message" =>  "El Producto esta siendo usado",
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
                "message" => "Producto no encontrado"
            ];
        }
        return $response;
    }
    public function actionProducts()
    {
        $params = Yii::$app -> getRequest() -> getBodyParams();
        $isStockActive = isset($params['stockActive']) ? $params['stockActive'] : null;
        $products = Producto::find()
                    ->select(['producto.*', 'categoria.nombre As nombre_categoria'])
                    ->join('LEFT JOIN', 'categoria', 'categoria.id = producto.categoria_id')
                    ->andfilterWhere(['stock_active' => $isStockActive])
                    ->asArray()
                    ->all();
        $response = [
            'success' => true,
            'message' => 'Lista de productos',
            'products' => $products
        ];
        return $response;
    }

    public function actionVarieties( $idProduct ) {
        $varieties = SubProducto::find()->where(['producto_id' => $idProduct])->all();
        if($varieties){
            $response = [
                'success' => true,
                'message' => 'Lista de subproductos',
                'varieties' => $varieties
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'No existen subproductos del producto.',
                'varieties' => $varieties
            ];
        }
        return $response;
    }

    public function actionDisableProduct( $idProduct ){
        $product = Producto::findOne($idProduct);
        if($product){
            $product -> estado = 'Inactivo';
            if($product -> save()){
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
}
