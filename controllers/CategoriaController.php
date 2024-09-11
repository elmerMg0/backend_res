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

    public function actionIndex($name, $pageSize = 5)
    {
        if ($name === 'undefined') $name = null;
        $query = Categoria::find()
            ->where(['categoria_id' => null])
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

    public function actionGetCategories()
    {
        $categories = Categoria::find()
            ->where(['estado' => 'Activo'])
            ->orderBy(['id' => 'SORT_ASC'])
            ->all();

        if ($categories) {

            $response = [
                'success' => true,
                'message' => 'Lista de categorias',
                'categories' => $categories,
            ];
        } else {
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
        if ($file) {
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
                ];
            } else {
                Yii::$app->getResponse()->setStatusCode(422, "Data Validation Failed.");
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
                if (file_exists($imageOld) && $url_image) {
                    unlink($imageOld);
                    /* Eliminar */
                }
                $fileName = uniqid() . '.' . $image->getExtension();
                $image->saveAs(Yii::getAlias('@app/web/upload/') . $fileName);
                $imageNew = Yii::getAlias('@app/web/upload/' . $fileName);
                if (file_exists($imageNew)) {
                    $category->url_image = $fileName;
                } else {
                    return $response = [
                        'success' => false,
                        'message' => 'Ocurrio un error!',
                    ];
                }
            }
            if ($category->save()) {
                $response = [
                    'success' => true,
                    'message' => 'Categoria actualizada correctamente',
                ];
            } else {
                Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed');
                $response = [
                    'success' => false,
                    'message' => 'Existe errores en los campos',
                    'error' => $category->errors
                ];
            }
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'Categoria no encontrada',
            ];
        }
        return $response;
    }

    public function actionCategory($idCategory)
    {
        $category = Categoria::find()
            ->where(['id' => $idCategory])
            ->with('categorias')
            ->asArray()
            ->one();
        if ($category) {
            $response = [
                'success' => true,
                'message' => 'Accion realizada correctamente',
                'record' => $category
            ];
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'No existe el Categoria',
                'record' => $category
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
                $pathFile = Yii::getAlias('@webroot/upload/' . $url_image);
                if (file_exists($pathFile)) {
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
        $categories = Categoria::find()
            ->where(['estado' => true, 'categoria_id' => null])
            ->with('categorias')
            ->asArray()
            ->all();
        $response = [
            'success' => true,
            'message' => 'Lista de categorias',
            'categories' => $categories
        ];
        return $response;
    }

    public function actionGetProductsByCategory($idCategory)
    {
        $category = Categoria::findOne($idCategory);
        if ($category) {
            $products = Producto::find()
                ->where(['estado' => true, 'categoria_id' => $idCategory])
                ->all();
            $response = [
                "success" => true,
                "message" => "Lista de productos por categoria",
                "category" => $category,
                "products" => $products
            ];
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                "success" => false,
                "message" => "Categoria no encontrada",
            ];
        }
        return $response;
    }

    public function actionGetCategoryWithProducts()
    {
        $query = Categoria::find()
            ->where(['estado' => true, 'categoria_id' => null])
            ->with(['categorias' => function ($query) {
                $query->with(['productos' => function ($query) {
                    $query
                        ->select(['producto.id', 'producto.categoria_id', 'producto.nombre', 'producto.url_image', 'producto.precio_venta', 'producto.estado', 'producto.id as producto_id', 'producto.area_impresion_id', 'producto.costo_compra'])
                        ->andWhere(['estado' => true])
                        ->with('comentarios')
                        ->with(['grupoModificadores' => function ($query) {
                            $query->select(['grupo_modificadores.*', 'catalogo_grupo_modificadores.descripcion'])
                                ->with(['grupoModificadoresDetalles' => function ($query) {
                                    $query->select(['grupo_modificadores_detalle.*', 'producto.nombre', 'producto.area_impresion_id'])
                                        ->innerJoin('producto', 'producto.id = grupo_modificadores_detalle.producto_id');
                                }])
                                ->innerJoin('catalogo_grupo_modificadores', 'catalogo_grupo_modificadores.id = grupo_modificadores.catalogo_grupo_modificadores_id')
                                ->orderBy(['secuencia_boton' => 'SORT_ASC']);
                        }])
                        ->with(['paquetes0' => function ($query) {
                            $query->select(['paquete.*', 'producto.categoria_id', 'producto.nombre', 'producto.url_image', 'producto.precio_venta', 'producto.estado', 'producto.id as producto_id', 'producto.area_impresion_id', 'paquete.id'])
                                ->innerJoin('producto', 'paquete.producto_id = producto.id')
                                ->andWhere(['estado' => true])
                                ->orderBy(['id' => 'SORT_ASC']);
                        }])
                        ->orderBy(['id' => 'SORT_ASC']);
                }]);
            }])
            ->with(['productos' => function ($query) {
                $query
                    ->select(['producto.id', 'producto.categoria_id', 'producto.nombre', 'producto.url_image', 'producto.precio_venta', 'producto.estado', 'producto.id as producto_id', 'producto.area_impresion_id', 'producto.costo_compra'])
                    ->andWhere(['estado' => true])
                    ->with('comentarios')
                    ->with(['paquetes0' => function ($query) {
                        $query->select(['paquete.*', 'producto.categoria_id', 'producto.nombre', 'producto.url_image', 'producto.precio_venta', 'producto.estado', 'producto.id as producto_id', 'producto.area_impresion_id', 'paquete.id'])
                            ->innerJoin('producto', 'paquete.producto_id = producto.id')
                            ->andWhere(['estado' => true])
                            ->orderBy(['id' => 'SORT_ASC']);
                    }])
                    ->with(['grupoModificadores' => function ($query) {
                        $query->select(['grupo_modificadores.*', 'catalogo_grupo_modificadores.descripcion'])
                            ->with(['grupoModificadoresDetalles' => function ($query) {
                                $query->select(['grupo_modificadores_detalle.*', 'producto.nombre', 'producto.area_impresion_id'])
                                    ->innerJoin('producto', 'producto.id = grupo_modificadores_detalle.producto_id');
                            }])
                            ->innerJoin('catalogo_grupo_modificadores', 'catalogo_grupo_modificadores.id = grupo_modificadores.catalogo_grupo_modificadores_id')
                            ->orderBy(['secuencia_boton' => 'SORT_ASC']);
                    }])
                    ->orderBy(['id' => 'SORT_ASC']);
            }])
            ->orderBy(['id' => 'SORT_ASC'])
            ->asArray()
            ->all();
        if ($query) {
            $response = [
                'success' => true,
                'message' => 'Lista de categorias',
                'categories' => $query
            ];
        } else {
            $response = [
                'success' => true,
                'message' => 'Lista de categorias',
                'categories' => []
            ];
        }
        return $response;
    }
    public function actionDisableCategory($idCategory)
    {
        $category = Categoria::findOne($idCategory);
        if ($category) {
            $category->estado = 'Inactivo';
            if ($category->save()) {
                $response = [
                    'success' => true,
                    'message' => 'Producto actualizado'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Ocurrio un error!'
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

    public function actionExistsPeriod()
    {
        $period = Periodo::find()
            ->where(['estado' => true])
            ->one();
        if ($period) {
            $response = [
                'success' => true,
                'message' => 'existe periodo activo',
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'En este instante está fuera de los horarios de atención',
            ];
        }
        return $response;
    }

    public function actionSubCategory()
    {
        $subCategories = Yii::$app->getRequest()->getBodyParams();

        $transaction = Yii::$app->db->beginTransaction();
        try {
            foreach ($subCategories as $subCategory) {
                $model = new Categoria();
                if (isset($subCategory['id'])) {
                    $model = Categoria::findOne($subCategory['id']);
                }
                $model->load($subCategory, '');
                if (!$model->save()) {
                    throw new \Exception(json_encode($model->errors));
                }
            }
            $transaction->commit();
            $response = [
                'success' => true,
                'message' => 'Sub categorias creadas exitosamente',
            ];
        } catch (\Exception $e) {
            $transaction->rollBack();
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }

        return $response;
    }
}
