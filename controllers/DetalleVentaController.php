<?php

namespace app\controllers;

use app\models\DetalleVenta;
use Yii;
use app\models\Venta;
use yii\db\Query;

class DetalleVentaController extends \yii\web\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors["verbs"] = [
            "class" => \yii\filters\VerbFilter::class,
            "actions" => [
                'index' => ['get'],
                'get-detail-period' => ['GET'],
                'get-sale-detail' => ['GET'],
                'get-reports-by-week' => ['POST']
            ]
        ];
        $behaviors['authenticator'] = [         	
            'class' => \yii\filters\auth\HttpBearerAuth::class,         	
            'except' => ['options']     	
        ];

        $behaviors['access'] = [
            'class' => \yii\filters\AccessControl::class,
            'only' => ['get-best-seller-product', 'index', 'get-sale-detail', 'get-reports-by-week'], // acciones a las que se aplicará el control
            'except' => [''],    // acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['get-best-seller-product', 'index', 'get-sale-detail', 'get-reports-by-week'], // acciones que siguen esta regla
                    'roles' => ['administrador', 'cajero'] // control por roles  permisos
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

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionGetBestSellerProduct($quantity){
        $detail = DetalleVenta::find()
                    ->select(['sum(cantidad) as cantidad', 'producto.nombre', 'producto.id'])
                    ->join('LEFT JOIN', 'producto', 'producto.id=detalle_venta.producto_id')
                    ->groupBy(['producto_id', 'producto.nombre', 'producto.id' ])
                    ->orderBy(['cantidad' => SORT_DESC])
                    ->where(['producto.tipo' => 'comida'])
                    ->andWhere(['<>', 'detalle_venta.estado', 'cancelado'])
                    ->asArray()
                    ->limit($quantity)
                    ->all();
        if($detail){
            $response = [
                'success' => true,
                'message' => 'Productos mas vendidos',
                'list' => $detail
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'no existen ventas aun!',
                'list' => $detail
            ];
        }
        return $response;
    }

    public function actionGetSaleDetail( $idSale ){
        $saleInfo = Venta::find()
                    ->select(['venta.*', 'usuario.username', 'cliente.nombre as cliente', 'cliente.celular', 'cliente.direccion', 'cliente.descripcion_domicilio', 'mesa.nombre as mesa'])
                    ->where(['venta.id' => $idSale])
                    ->innerJoin('usuario', 'usuario.id = venta.usuario_id')
                    ->innerJoin('cliente', 'cliente.id = venta.cliente_id')
                    ->innerJoin('mesa','mesa.id = venta.mesa_id')
                    ->asArray()
                    ->one();

        $products = DetalleVenta::find()
                         ->select(['producto.nombre','producto.precio_venta', 'detalle_venta.cantidad', 'producto.id as producto_id'])
                        ->innerJoin('producto', 'producto.id = detalle_venta.producto_id')
                        ->where(['venta_id' => $idSale])
                        ->andWhere(['<>','detalle_venta.estado', 'cancelado'])
                        ->asArray()
                        ->all();
        $response = [
            'success' => true, 
            'message' => 'Detalle de venta',
            'saleInfo' => $saleInfo,
            'products' => $products
        ];
        return $response;
    }

    public function actionGetReportsByWeek (){
        $query = new Query();
        $params = Yii::$app->getRequest()->getBodyParams(); 
        $type = $params['tipo'];
        $beginDate = $params['fechaInicio'];
      
        $productIds = isset($params['productIds']) ? $params['productIds'] : null;
        $condition = ['or'];

        if($productIds){
            foreach ($productIds as $productId) {
                $condition[] = ['dv.producto_id' => $productId];
            }
        }

        //return $productIds;
        $fechaFinWhole = $params['fechaFin'] . ' ' . '23:59:00.000';
        $expressions = [
            'week' => 'EXTRACT(WEEK FROM FECHA) as weekNumber',
            'month' => "CONCAT(EXTRACT(YEAR FROM fecha), '-', LPAD(EXTRACT(MONTH FROM fecha)::text, 2, '0')) as Fecha",
            'day' => 'DATE_TRUNC(\'day\', fecha) as dayNumber'
        ];

        $query->select([
            'dv.producto_id',
            'p.nombre',
            new \yii\db\Expression($expressions[$type]),
            //new \yii\db\Expression('CONCAT(EXTRACT(YEAR FROM fecha), '/', LPAD(EXTRACT(MONTH FROM fecha)::text, 2, "0"), '/', LPAD(EXTRACT(WEEK FROM fecha)::text, 2, "0")) AS anio_mes_semana'),
            new \yii\db\Expression("SUM(dv.cantidad) OVER (PARTITION BY EXTRACT($type FROM fecha), dv.producto_id) AS cantidadTotal"),
        ])
        ->distinct()
        ->from('detalle_venta dv')
        ->innerJoin('producto p', 'dv.producto_id = p.id')
        ->innerJoin('venta v', 'v.id = dv.venta_id')
        ->where(['between', 'fecha', $beginDate, $fechaFinWhole])
        //todos los productos seleccionados
        ->andWhere($condition)
        ->orderBy(['dv.producto_id' => SORT_ASC]);
        
           // Ejecutar la consulta y obtener los resultados
        $response = [
            'success' => true,
            'message' => 'Reportes por semana',
            'result' => $query -> all()
        ];
        return $response;
    }
}
