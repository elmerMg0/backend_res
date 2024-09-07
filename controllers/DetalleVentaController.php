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
                    'roles' => ['administrador', 'mesero', 'cajero'] // control por roles  permisos
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

  

    public function actionGetSaleDetail( $idSale ){
        $saleInfo = Venta::find()
                    ->select(['venta.*', 'usuario.username', 'mesa.nombre as mesa', 'cliente.nombre as customer', 'area_venta.nombre as area', 'tipo_pago.descripcion as tipo_pago', 'venta_descuento.valor'])
                    ->innerJoin('usuario', 'usuario.id = venta.usuario_id')
                    ->leftJoin('mesa', 'mesa.id=venta.mesa_id')
                    ->innerJoin('cliente', 'cliente.id=venta.cliente_id')
                    ->innerJoin('area_venta', 'area_venta.id=venta.area_venta_id')
                    ->leftJoin('tipo_pago', 'tipo_pago.id=venta.tipo_pago_id')
                    ->leftJoin('venta_descuento', 'venta_descuento.venta_id=venta.id')
                    ->where(['=', 'venta.id', $idSale])
                    ->orderBy(['venta.id' => SORT_DESC])
                    ->asArray()
                    ->one();

        $saleDetailFull = DetalleVenta::find()
                    ->select(['detalle_venta.*', 'producto.nombre', 'producto.area_impresion_id'])
                    ->innerJoin('producto', 'producto.id=detalle_venta.producto_id')
                    ->where(['venta_id' => $idSale, 'detalle_venta_id' => null])
                    //->andWhere(['<>', 'detalle_venta.estado', 'cancelado'])
                    ->orderBy(['id' => SORT_DESC])
                    ->with(['detalleVentas' => function ($query) {
                        $query->select(['detalle_venta.*', 'producto.nombre', 'producto.area_impresion_id'])
                            ->innerJoin('producto', 'producto.id=detalle_venta.producto_id');
                    }])
                    ->asArray()
                    ->all();

        $response = [
            'success' => true, 
            'message' => 'Detalle de venta',
            'saleInfo' => $saleInfo,
            'products' => $saleDetailFull
        ];
        return $response;
    }

    public function actionGetReportsByWeek (){
        $query = new Query();
        $params = Yii::$app->getRequest()->getBodyParams(); 
        extract($params);
        $type = $tipo;
        $beginDate = $fechaInicio;
      
        $productIds = $productIds ?? null;
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
            'month' => 'DATE_TRUNC(\'month\' ,fecha) as Fecha',
            'day' => 'DATE_TRUNC(\'day\', fecha) as dayNumber'
        ];

        $query->select([
            'dv.producto_id',
            'p.nombre',
            new \yii\db\Expression($expressions[$type]),
            //new \yii\db\Expression('CONCAT(EXTRACT(YEAR FROM fecha), '/', LPAD(EXTRACT(MONTH FROM fecha)::text, 2, "0"), '/', LPAD(EXTRACT(WEEK FROM fecha)::text, 2, "0")) AS anio_mes_semana'),
            new \yii\db\Expression("SUM(dv.cantidad) OVER (PARTITION BY DATE_TRUNC('$type' , fecha), dv.producto_id ) AS cantidadTotal"),
        ])
        ->distinct()
        ->from('detalle_venta dv')
        ->innerJoin('producto p', 'dv.producto_id = p.id')
        ->innerJoin('venta v', 'v.id = dv.venta_id')
        ->where(['>=', 'fecha', $beginDate])
        ->andWhere(['<=', 'fecha', $fechaFinWhole])
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
