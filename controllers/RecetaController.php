<?php

namespace app\controllers;

use app\models\AsignacionAreaAlmacen;
use app\models\Producto;
use app\models\Receta;
use Exception;
use Yii;

class RecetaController extends \yii\web\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors["verbs"] = [
            "class" => \yii\filters\VerbFilter::class,
            "actions" => [
                'index' => ['get'],
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

    public function actionUpdate($idProduct)
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            $params = Yii::$app->getRequest()->getBodyParams();
            extract($params);
            /* Crear o actualizar recetas */
            for($i = 0; $i < count($suppliesList); $i++){
                $recipeItem = Receta::find() -> where(['producto_id' => $idProduct, 'insumo_id' => $suppliesList[$i]["insumo_id"]]) -> one();
                if(!$recipeItem){
                    $recipeItem = new Receta();
                }
                $recipeItem -> load ($suppliesList[$i], '');
                if(!$recipeItem->save()){
                    throw new Exception('No se pudo crear la receta');
                }
            }

            /* Eliminar o borrar asignaciones */
            for($i = 0; $i < count($suppliesIdsToDelete); $i++){
                $model = Receta::findOne($suppliesIdsToDelete[$i]);
                if($model){
                    $assigns = AsignacionAreaAlmacen::find() -> where(['receta_id' => $suppliesIdsToDelete[$i]]) -> all();
                    foreach($assigns as $assign){
                        $assign -> delete();
                    }
                    $model -> delete();
                }
            }   

            //Update costo compra.
            $product = Producto::findOne($idProduct);
            $product -> costo_compra = $params['cost'];
            if(!$product -> save()){
                throw new Exception('No se pudo actualizar el costo de la receta');
            }

            Yii::$app->getResponse()->setStatusCode(201);
            $transaction->commit();
            $response = [
                'success' => true,
                'message' => 'Receta actualizada exitosamente',
            ];
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::$app->getResponse()->setStatusCode(500);
            $response = [
                'success' => false,
                'message' => 'ocurrio un error',
                'Error' => $e->getMessage()
            ];
        }

        return $response;
    }

    public function actionDelete( $idsupplies ){
        $supplies = Receta::findOne($idsupplies);
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
