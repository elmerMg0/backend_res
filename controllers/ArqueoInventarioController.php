<?php

namespace app\controllers;

use app\models\ArqueoInventario;
use app\models\DetalleArqueoInventario;
use app\models\DetallePresArqueoInventario;
use app\services\InventaryService;
use Yii;
use yii\data\Pagination;

class ArqueoInventarioController extends \yii\web\Controller
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors["verbs"] = [
            "class" => \yii\filters\VerbFilter::class,
            "actions" => [
                'index' => ['post'],
                'create' => ['post'],
                'update' => ['put', 'post'],
            ]
        ];
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['options']
        ];
        $behaviors['access'] = [
            'class' => \yii\filters\AccessControl::class,
            'only' => ['create', 'index'], // acciones a las que se aplicará el control
            'except' => [''],    // acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['index', 'create'], // acciones que siguen esta regla
                    'roles' => ['administrador', 'configurador'] // control por roles  permisos
                ],
            ],
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
        $params = Yii::$app->getRequest()->getBodyParams();
        $user = isset($params['usuarioId'])? $params['usuarioId'] : null;
        $fechaIni = isset($params['fechaInicio']) ? $params['fechaInicio'] :  null;
        $fechaFinWhole =  isset($params['fechaFin']) ? $params['fechaFin'] . ' 23:59:58.0000' : null;
        $warehouseId =  isset($params['almacen_id']) ? $params['almacen_id'] : null;


        $query = ArqueoInventario::find()
            ->select(['arqueo_inventario.fecha', 'usuario.nombres as usuario', 'arqueo_inventario.id', 'almacen.descripcion as almacen'])
            ->innerJoin('usuario','usuario.id = arqueo_inventario.usuario_id')
            ->innerJoin('almacen','almacen.id = arqueo_inventario.almacen_id')
            ->andFilterWhere(['>=', 'fecha', $fechaIni])
            ->andFilterWhere(['<=', 'fecha', $fechaFinWhole])
            ->andFilterWhere(['usuario_id' => $user])
            ->andFilterWhere(['almacen_id' => $warehouseId])
            ->orderBy(['arqueo_inventario.id' => SORT_DESC])
            ->asArray();

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count()
        ]);

        $inventaryAudits = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        if ($inventaryAudits) {
            $currentPage = $pagination->getPage() + 1;
            $totalPages = $pagination->getPageCount();
            $response = [
                'success' => true,
                'message' => 'lista de arqueo de inventarios ',
                'pageInfo' => [
                    'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                    'previus' => $currentPage == 1 ? null : $currentPage - 1,
                    'count' => count($inventaryAudits),
                    'page' => $currentPage,
                    'start' => $pagination->getOffset(),
                    'totalPages' => $totalPages,
                ],
                'inventaryAudits' => $inventaryAudits
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'No existen inventarios',
                'sales' => []
            ];
        }
        return $response;
    }

    public function actionCreate(){
        $params = Yii::$app->getRequest()->getBodyParams();
        $inventaryService = new InventaryService();
        return $inventaryService->createInventary($params);
    }

    public function actionDetails($id){
        $inventaryDetails = DetalleArqueoInventario::find()
                            ->select(['insumo.descripcion', 'detalle_arqueo_inventario.*', 'unidad_medida.abreviatura as unidad_medida'])
                            ->innerjoin('insumo', 'detalle_arqueo_inventario.insumo_id = insumo.id')
                            ->innerjoin('unidad_medida', 'unidad_medida.id = insumo.unidad_medida_id')
                            ->where(['arqueo_inventario_id' => $id])
                            ->asArray()
                            ->all();
        $response = [
            'success' => true,
            'message' => 'Detalle inventario',
            'records' =>  $inventaryDetails
        ];

        return $response;
    }
}
