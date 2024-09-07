<?php

namespace app\controllers;

use app\models\Compra;
use app\services\CompraService;
use Yii;
use yii\data\Pagination;

class CompraController extends \yii\web\Controller
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

    public function actionCreate(){
        $params = Yii::$app->getRequest()->getBodyParams();
        $compraService = new CompraService();
        return $compraService->createCompra($params);
    }

    public function actionIndex($pageSize = 10){

        $params = Yii::$app->getRequest()->getBodyParams();
        $user = isset($params['usuarioId'])? $params['usuarioId'] : null;
        $fechaIni = isset($params['fechaInicio'])? $params['fechaInicio'] : null;
        $fechaFin = isset($params['fechaFin'])? $params['fechaFin'] . ' 23:59:58.0000' : null;
        $estado = isset($params['estado'])? $params['estado'] : null;
        $proveedor = isset($params['proveedor_id'])? $params['proveedor_id'] : null;

        $query = Compra::find()
                    ->select(['compra.*', 'usuario.nombres as usuario', 'proveedor.nombre as proveedor'])
                    ->innerJoin('usuario', 'compra.usuario_id = usuario.id')
                    ->innerJoin('proveedor', 'proveedor.id = compra.proveedor_id')
                    ->andFilterWhere(['>=', 'fecha', $fechaIni])
                    ->andFilterWhere(['<=', 'fecha', $fechaFin])
                    ->andFilterWhere(['usuario_id' => $user])
                    ->andFilterWhere(['compra.estado' => $estado])
                    ->andFilterWhere(['proveedor_id' => $proveedor])
                    ->asArray();

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count(),
        ]);

        $data = $query
            ->orderBy('compra.id DESC')
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $currentPage = $pagination->getPage() + 1;
        $totalPages = $pagination->getPageCount();
        $response = [
            'success' => true,
            'message' => 'lista de compras',
            'pageInfo' => [
                'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                'previus' => $currentPage == 1 ? null : $currentPage - 1,
                'count' => count($data),
                'page' => $currentPage,
                'start' => $pagination->getOffset(),
                'totalPages' => $totalPages,
            ],
            'records' => $data
        ];
        return $response;
    }

    public function actionConfirmPayment($id){
        $purchase = Compra::findOne($id);
        $response = [
            'success' => false,
            'message' => 'Error al confirmar el pago',
            'purchase' => $purchase
        ];
        
        if($purchase){
            $purchase->estado = 'Pagado';
            if(!$purchase->save()){
                $response = [
                    'success' => false,
                    'message' => 'Error al confirmar el pago',
                    'purchase' => $purchase
                ];
            }else{
                $response = [
                    'success' => true,
                    'message' => 'Se ha confirmado el pago',
                    'purchase' => $purchase
                ];
            }
        }
        return $response;

    }
}
