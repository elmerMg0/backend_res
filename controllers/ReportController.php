<?php
namespace app\controllers;

use app\models\DetalleVenta;
use app\models\Gasto;
use app\models\RegistroGasto;
use app\models\Venta;
use Yii;
use yii\data\Pagination;
use yii\db\Expression;
use yii\db\Query;

class ReportController extends \yii\web\Controller
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
                'get-reports-by-week' => ['POST'],
                'get-total-sale-month' => ['get'],
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

    public function beforeAction( $action ){
        if (Yii::$app->getRequest()->getMethod() === 'OPTIONS') {         	
            Yii::$app->getResponse()->getHeaders()->set('Allow', 'POST GET PUT');         	
            Yii::$app->end();     	
        }   

        $this->enableCsrfValidation = false;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

    public function actionGetBestSellerProduct(){
        $params = Yii::$app -> getRequest() -> getBodyParams();
        $type = isset($params['type']) ? $params['type'] :  null;
        $dateStart = isset($params['dateStart']) ? $params['dateStart'] :  null;
        $dateEnd = isset($params['dateEnd']) ? $params['dateEnd'] : null;
        $detail = DetalleVenta::find()
                    ->select(['sum(cantidad) as cantidad','detalle_venta.precio_venta' ,'producto.nombre'])
                    ->innerJoin('producto', 'producto.id=detalle_venta.producto_id')
                    ->innerJoin('categoria', 'categoria.id=producto.categoria_id')
                    ->innerJoin('clasificacion_grupo', 'clasificacion_grupo.id=categoria.clasificacion_grupo_id')
                    ->groupBy(['producto_id', 'producto.nombre', 'detalle_venta.precio_venta'])
                    ->orderBy(['cantidad' => SORT_DESC])
                    ->where(['<>', 'detalle_venta.estado', 'cancelado'])
                    ->andFilterWhere(['clasificacion_grupo.id' => $type])
                    ->andFilterWhere(['>=', 'create_ts', $dateStart])
                    ->andFilterWhere(['<=', 'create_ts', $dateEnd])
                    ->asArray()
                    ->limit($params['quantity'])
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

    public function actionGetTotalSaleMonth($month, $year)
    {

        $expresion = 'DATE(DATE_TRUNC(\'month\', fecha)) as dateMonth';
        $monthNumber = 'extract ( month from fecha) ';
        $query = Venta::find()
/*             ->select([
                new Expression($expresion),
                'SUM(cantidad_total) as totalVentas',
            ]) */
            ->where(['estado' => 'pagado', 'EXTRACT(Year FROM fecha)' => $year])
            ->andWhere(['=', new Expression($monthNumber), intval($month)])
            ->asArray()
            ->sum('cantidad_total');

        $expense =  Gasto::find()
                   /*  ->select([
                        new Expression($expresion),
                        'SUM(total) as totalVentas',
                    ]) */
                    ->where(['pagado' => true, 'EXTRACT(Year FROM fecha)' => $year])
                    ->andWhere(['=', new Expression($monthNumber), intval($month)])
                   /*  ->groupBy(['DATE(DATE_TRUNC(\'month\', fecha))']) */
                    ->asArray()
                    ->sum('total');

        
        $saleCost = Venta::find()
                    ->innerJoin('detalle_venta', 'detalle_venta.venta_id = venta.id')
                    ->where(['venta.estado' => 'pagado' ,'EXTRACT(Year FROM fecha)' => $year])
                    ->andWhere(['<>', 'detalle_venta.estado', 'cancelado'])
                    ->andWhere(['=', new Expression($monthNumber), intval($month)])
                    ->asArray()
                    ->sum('detalle_venta.costo_compra * detalle_venta.cantidad');

        $month = str_pad($month, 2, "0", STR_PAD_LEFT);
        $response = [
            'success' => true,
            'message' => 'Reportes global',
            'record' => [
                'sales' => $query,
                'expenses' => $expense,
                'saleCost' => $saleCost,
                'date' => $year . '-' . $month
            ]
        ];
       
        return $response;
    }

  
  
    public function actionSalesByDay($pageSize = 7)
    {
        //fecha inicio/ fecha fin/ usuario
        $params = Yii::$app->getRequest()->getBodyParams();
        extract($params);
        $fechaFinWhole = $fechaFin . ' ' . '23:59:00.000';
        $usuarioId = $usuarioId === 'todos' ? null : $usuarioId;
        $salesForDay = Venta::find()
            ->select(['DATE(fecha) AS fecha', 'SUM(cantidad_total) AS total', 'usuario.nombres', 'usuario.id as userId'])
            ->joinWith('usuario')
            ->Where(['venta.estado' => 'pagado'])
            ->andFilterWhere(['usuario_id' => $usuarioId])
            ->andWhere(['>=', 'fecha', $fechaInicio])
            ->andWhere(['<=', 'fecha', $fechaFinWhole])
            ->orderBy(['fecha' => SORT_DESC])
            ->groupBy(['DATE(fecha)', 'usuario.nombres', 'usuario.id'])
            ->asArray();

       /*  $expenses = RegistroGasto::find()
            ->select(['DATE(fecha) AS fecha', 'SUM(total) AS total', 'usuario.nombres', 'usuario.id as userId'])
            ->innerJoin('usuario', 'usuario.id = registro_gasto.usuario_id')
            ->Where(['registro_gasto.estado' => 'pagado'])
            ->andFilterWhere(['usuario_id' => $usuarioId])
            ->andWhere(['>=', 'fecha', $fechaInicio])
            ->andWhere(['<=', 'fecha', $fechaFinWhole])
            ->orderBy(['fecha' => SORT_DESC])
            ->groupBy(['DATE(fecha)', 'usuario.nombres', 'usuario.id'])
            ->asArray(); */

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $salesForDay->count()
        ]);

    /*     $paginationExpenses = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $expenses->count()
        ]);
 */


        $sales = $salesForDay
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

       /*  $expenses = $expenses
            ->offset($paginationExpenses->offset)
            ->limit($paginationExpenses->limit)
            ->all(); */

        if ($sales) {
            $currentPage = $pagination->getPage() + 1;
            $totalPages = $pagination->getPageCount();
            $response = [
                'success' => true,
                'message' => 'lista de clientes',
                'pageInfo' => [
                    'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                    'previus' => $currentPage == 1 ? null : $currentPage - 1,
                    'count' => count($sales),
                    'page' => $currentPage,
                    'start' => $pagination->getOffset(),
                    'totalPages' => $totalPages,
                ],
                'sales' => $sales,
          /*       'expenses' => $expenses */
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Lista de ventas por dia',
                'sales' => []
            ];
        }
        return $response;
    }
}