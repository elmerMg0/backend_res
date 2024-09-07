<?php

namespace app\controllers;

use app\models\GrupoInsumo;
use Exception;
use Yii;
use yii\data\Pagination;

class GrupoInsumoController extends \yii\web\Controller
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
        $query = GrupoInsumo::find()
                    ->select(['grupo_insumo.id', 'grupo_insumo.clasificacion_grupo_id' ,'grupo_insumo.descripcion', 'grupo_insumo.estado', 'clasificacion_grupo.descripcion as clasificacion'])
                    ->innerJoin('clasificacion_grupo', 'clasificacion_grupo.id = grupo_insumo.clasificacion_grupo_id')
                    ->andFilterWhere(['LIKE', 'UPPER(grupo_insumo.descripcion)',  strtoupper($description)]);

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
            'message' => 'lista de grupo de insumos',
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

    public function actionGetSuppliesGroup ($estado = null) {
        $records = GrupoInsumo::find()
                      ->filterWhere(['estado' => $estado])
                      ->orderBy(['id' => 'SORT_ASC'])             
                      ->all();

        if($records){
            $response = [
                'success' => true,
                'message' => 'Lista de grupo de insumos',
                'records' => $records,
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'No existen grupo de insumos',
                'records' => [],
            ];
        }
        return $response;
    }

    public function actionCreate()
    {
        $supplieGroup = new GrupoInsumo();
        $params = Yii::$app->getRequest()->getBodyParams();
      
        try {
        $supplieGroup->load($params, '');

            if ($supplieGroup->save()) {
                Yii::$app->getResponse()->setStatusCode(201);
                $response = [
                    'success' => true,
                    'message' => 'Grupo de insumo creado exitosamente',
                    'fileName' => $supplieGroup
                ];
            } else {
                Yii::$app->getResponse()->setStatusCode(422,"Data Validation Failed.");
                $response = [
                    'success' => false,
                    'message' => 'Existen errores en los campos',
                    'errors' => $supplieGroup->errors
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


    public function actionUpdate($idSuppliesGroup)
    {
        $supplieGroup = GrupoInsumo::findOne($idSuppliesGroup);
        if ($supplieGroup) {
            $data = Yii::$app->getRequest()->getBodyParams();
            $supplieGroup->load($data, '');
            try {
                if ($supplieGroup->save()) {
                    $response = [
                        'success' => true,
                        'message' => 'Grupo de insumos actualizado correctamente',
                        'supplieGroup' => $supplieGroup
                    ];
                } else {
                    Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed');
                    $response = [
                        'success' => false,
                        'message' => 'Existe errores en los campos',
                        'error' => $supplieGroup->errors
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

    public function actionDisable( $idSuppliesGroup ){
        $supplieGroup = GrupoInsumo::findOne($idSuppliesGroup);
        if($supplieGroup){
            $supplieGroup -> estado = false;
            if($supplieGroup -> save()){
                $response = [
                    'success' => true,
                    'message' => 'Grupo de insumo actualizado'
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
