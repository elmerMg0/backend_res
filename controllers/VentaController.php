<?php

namespace app\controllers;

use app\models\DetalleVenta;
use app\models\Mesa;
use app\models\Venta;
use app\models\Periodo;
use Yii;
use yii\db\Query;
use yii\data\Pagination;

class VentaController extends \yii\web\Controller
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

    public function actionIndex()
    {
        return $this->render('index');
    }
    public function actionCreate($userId=0)
    {
        /* SI es pedido de app, entonces la venta se carga al ultimo periodo aperturado */
        if($userId === 0 ){
            $period = Periodo::find()
                           ->where(['estado' => true])
                           ->one();
            if($period){
                $userId = $period -> usuario_id;
            }else{
                return  [
                    'success' => false,
                    'message' => 'Ocurrio un error',
                ];
            }
        }

        $params = Yii::$app->getRequest()->getBodyParams();
        $numberOrder = Venta::find()->all();
        $orderDetail = $params['orderDetail'];
        $sale = new Venta();
        date_default_timezone_set('America/La_Paz');
        $sale -> fecha = date('Y-m-d H:i:s');
        $sale->cantidad_total = intval($params['cantidadTotal']);
        $sale->cantidad_cancelada = $params['cantidadPagada'];
        $sale->usuario_id = $userId;
        $sale->numero_pedido = count($numberOrder) + 1;
        $sale->estado = $params['estado'];
        $sale->tipo_pago = $params['tipoPago'];
        $sale->tipo = $params['tipo'];
        $sale->nombre = $params['nombre'];
        if($params['tipo'] === 'pedidoApp'){
            $sale->tipo_entrega = $params['tipo_entrega'];
            $sale->telefono = $params['telefono'];
            $sale->hora = $params['hora'];
            $sale->descripcion_direccion = $params['descripcion_direccion'];
            $sale->direccion = $params['direccion'];
        }
        //$sale->cliente_id = $customerId;

        if ($sale->save()) {
            //agregar detalle de venta
            foreach ($orderDetail as $order) {
                $saleDetail = new DetalleVenta();
                $saleDetail->cantidad = $order['cantidad'];
                $saleDetail->producto_id = $order['id'];
                $saleDetail->venta_id =  $sale->id;
                if ($saleDetail->save()) {
                   
                }else{
                    Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed.');
                    return $response = [
                        'success' => false,
                        'message' => 'Existen errores en los parametros',
                        'data' => $saleDetail->errors
                    ];
                }
            }

            Yii::$app->getResponse()->setStatusCode(201);
            $response = [
                'success' => true,
                'message' => 'Su pedido se realizo exitosamente',
                'sale' => $sale
            ];
        } else {
            Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed.');
            $response = [
                'success' => false,
                'message' => 'failed update',
                'data' => $sale->errors
            ];
        }
        return $response;
    }

    /* Retorna la lista de ventas del periodo */
    public function actionGetSales($idPeriod, $idUser)
    {

        $period = Periodo::findOne($idPeriod);

        $sales = Venta::find()
            ->where(['fecha' >= $period->fecha_inicio, 'usuario_id' => $idUser])
            ->all();
        $response = [
            'success' => true,
            'message' => 'Lista de ventas',
            'sales' => $sales
        ];

        return $response;
    }

    public function actionGetInfoLineChart()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $fechaFinWhole = $params['fechaFin'] . ' ' . '23:59:00.000';
        if ($params['tipo'] === 'mes') {
            $salesForDay = (new Query())
                ->select(['DATE(DATE_TRUNC(\'month\', fecha)) AS mes', 'SUM(cantidad_total) AS total'])
                ->from('venta')
                ->where(['between', 'fecha', $params['fechaInicio'], $fechaFinWhole])
                ->andWhere(['venta.estado' => 'pagado'])
                ->groupBy(['mes'])
                ->orderBy(['mes' => SORT_ASC])
                ->all();
        } else {
            $salesForDay = Venta::find()
                ->select(['DATE(fecha) AS fecha', 'SUM(cantidad_total) AS total'])
                ->where(['between', 'fecha', $params['fechaInicio'], $fechaFinWhole])
                ->andWhere(['venta.estado' => 'pagado'])
                ->orderBy(['fecha' => SORT_ASC])
                ->groupBy(['DATE(fecha)'])
                ->asArray()
                ->all();
        }
        if ($salesForDay) {
            $response = [
                'success' => true,
                'message' => 'Lista de ventas por dia',
                'salesForDay' => $salesForDay
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'No existen ventas aun',
                'salesForDay' => $salesForDay
            ];
        }


        return $response;
    }

    public function actionGetSalesByDay($pageSize = 7)
    {
        //fecha inicio/ fecha fin/ usuario
        $params = Yii::$app->getRequest()->getBodyParams();

        $fechaFinWhole = $params['fechaFin'] . ' ' . '23:59:00.000';
        if ($params['usuarioId'] === 'todos') {
            $salesForDay = Venta::find()
                ->select(['DATE(fecha) AS fecha', 'SUM(cantidad_total) AS total', 'usuario.nombres'])
                ->joinWith('usuario')
                ->where(['between', 'fecha', $params['fechaInicio'], $fechaFinWhole])
                ->andWhere(['venta.estado' => 'pagado'])
                ->orderBy(['fecha' => SORT_ASC])
                ->groupBy(['DATE(fecha)', 'usuario.nombres'])
                ->asArray();
        } else {
            $salesForDay = Venta::find()
                ->select(['DATE(fecha) AS fecha', 'SUM(cantidad_total) AS total', 'usuario.nombres'])
                ->joinWith('usuario')
                ->Where(['usuario_id' => $params['usuarioId'], 'venta.estado' => 'pagado'])
                ->andWhere(['between', 'fecha', $params['fechaInicio'], $fechaFinWhole])
                ->orderBy(['fecha' => SORT_ASC])
                ->groupBy(['DATE(fecha)', 'usuario.nombres'])
                ->asArray();
        }

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $salesForDay->count()
        ]);

        $sales = $salesForDay
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

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
                'sales' => $sales
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

    /* Retorna la venta con su detalle de venta, segun un rango de fecha y usuario */
    public function actionGetSaleDetail($pageSize = 7)
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $fechaFinWhole = $params['fechaFin'] . ' 23:59:58.0000';

        if ($params['usuarioId'] === 'todos') {
            $query = Venta::find()
                ->select(['venta.*', 'usuario.username'])
                ->join('LEFT JOIN', 'usuario','usuario.id = venta.usuario_id')
                ->where(['between', 'fecha', $params['fechaInicio'], $fechaFinWhole])
                ->orderBy(['venta.id' => SORT_DESC])
                ->asArray();
        } else {
            $query = Venta::find()
                ->select(['venta.*', 'usuario.username'])
                ->join('LEFT JOIN', 'usuario','usuario.id = venta.usuario_id')
                ->Where(['usuario_id' => $params['usuarioId']])
                ->andWhere(['between', 'fecha', $params['fechaInicio'], $fechaFinWhole])
                ->orderBy(['venta.id' => SORT_DESC])
                ->asArray();
        }


        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count()
        ]);

        $sales = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

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
                'sales' => $sales
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


    public function actionGetSaleDetailAll($idPeriod, $idUser)
    {
        $period = Periodo::findOne($idPeriod);
        $sales = Venta::find()
        ->join('LEFT JOIN','usuario', 'usuario.id = venta.usuario_id')
        ->Where(['usuario_id' => $idUser, 'venta.estado' => 'pendiente', 'venta.tipo' => 'pedidoApp'])
        ->orWhere(['venta.estado' => 'enviado'])
        ->andWhere(['>=', 'fecha', $period -> fecha_inicio])
        ->orderBy(['id' => SORT_DESC])
        ->asArray()
        ->all();

   
        if ($sales) {
            $response = [
                'success' => true,
                'message' => 'lista de clientes',
                'sales' => $sales
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

    public function actionGetSaleDetailByPeriod ($pageSize = 7) {
        $params = Yii::$app->getRequest()->getBodyParams();
        $period = Periodo::findOne($params['idPeriod']);
        $query = Venta::find()
                ->select(['venta.*', 'usuario.username'])
                ->join('LEFT JOIN', 'usuario','usuario.id = venta.usuario_id')
                ->Where(['usuario_id' => $params['idUser']])
                ->andWhere(['>=', 'fecha', $period->fecha_inicio])
                ->orderBy(['venta.id' => SORT_DESC])
                ->asArray();

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count()
        ]);

        $sales = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        if ($sales) {
            $currentPage = $pagination->getPage() + 1;
            $totalPages = $pagination->getPageCount();
            $response = [
                'success' => true,
                'message' => 'lista de ventas',
                'pageInfo' => [
                    'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                    'previus' => $currentPage == 1 ? null : $currentPage - 1,
                    'count' => count($sales),
                    'page' => $currentPage,
                    'start' => $pagination->getOffset(),
                    'totalPages' => $totalPages,
                ],
                'sales' => $sales
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

    public function actionCancelSale ($idSale) {
        $sale = Venta::findOne($idSale);
        if($sale){
            $sale -> estado = 'cancelado';
            if($sale -> save()){
                $response = [
                    'success' => true,
                    'message' => 'Venta cancelada'
                ];
            }else{
                $response = [
                    'success' => false,
                    'message' => 'Ocurrio un error'
                ];
            }
        }else{
            $response = [
                'success' => false,
                'message' => 'Ocurrio un error'
            ];
        }
        return $response;
    }

    public function actionUpdateState($state, $idSale){
        $sale = Venta::findOne($idSale);
        $sale -> estado = $state;
        if($sale -> save()){
            $response = [
                'success' => true,
                'message' => 'Actualizado corretamente'
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'Ucurrio un error'
            ];
        }
        return $response;
    }

    /* Cantidad de productos por dia */
    public function actionGetProductsSaleByDay(){
        /* Fecha */
        $params = Yii::$app->getRequest()->getBodyParams();

        $query = (new \yii\db\Query())
            ->select([
                'producto.nombre AS nombre_producto',
                'producto.id As idProducto',
                'SUM(detalle_venta.cantidad) AS cantidad_vendida',
                'SUM(detalle_venta.cantidad * producto.precio_venta) AS total_dinero'
                ])
            ->from('venta')
            ->innerJoin('detalle_venta', 'venta.id = detalle_venta.venta_id')
            ->innerJoin('producto', 'detalle_venta.producto_id = producto.id')
            ->where(['DATE(venta.fecha)' => $params['fecha'], 'venta.estado' => 'pagado'])
            ->groupBy(['producto.nombre', 'producto.id'])
            ->orderBy(['cantidad_vendida' => SORT_DESC]);
        if($query){
            $response = [
                'success' => true,
                'message' => 'Lista de ventas de productos por dia',
                'reports' => $query->all()
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'No existen reportes.',
                'reports' => []
            ];
        }
        return $response;
    }

    public function actionCreateSale($idTable){
        $sale = Venta::find()->where(['mesa_id' => $idTable, 'estado' => 'consumiendo' ]) -> one();
        if($sale){
            /* Si ya hay mesa ocuapdo, recuperar detalle de venta(productos, cantidad) */
                $saleDetails = Venta::find()
                            ->select(['producto.*', 'detalle_venta.cantidad As cantidad', 'venta.id As idSale', 'detalle_venta.estado as estado'])
                            ->where(['mesa_id' => $idTable, 'venta.estado' => 'consumiendo' ])
                            ->innerJoin('detalle_venta', 'detalle_venta.venta_id=venta.id')
                            ->innerJoin('producto', 'producto.id=detalle_venta.producto_id')
                            ->asArray()
                            ->all();
                $response = [
                'success' => true,
                'message' => 'Info de venta',
                'saleDetails' => $saleDetails,
                'sale' => $sale
            ];
        }else{ 
            /* Crear venta */
            $params = Yii::$app -> getRequest() -> getBodyParams();
            $newSale = new Venta();
            $newSale -> load ($params, '');
            $numberOrder = Venta::find()->all();
            $newSale->numero_pedido = count($numberOrder) + 1;
            
            if($newSale -> save()){
                /* Actualizar el estado de la mesa DISPONIBLE -> OCUPADO */
                $table = Mesa::findOne($idTable);
                $table -> estado = 'ocupado';
           
                if($table -> save()){
                    $response = [
                        'success' => true,
                        'message' => 'Info de venta',
                        'saleDetails' => [],
                        'sale' => $newSale
                    ];
                }else{
                    $response = [
                        'success' => false,
                        'message' => 'Existe errores en los campos',
                        'error' => $table->errors
                    ];
                }
            }else{
                $response = [
                    'success' => false,
                    'message' => 'Existe errores en los campos',
                    'error' => $newSale->errors
                ];
            }
        }
        return $response;
    }
/* Eliminar detalle de venta y agregar nuevo detalle venta */
    public function actionUpdateSale($idSale){
        $params = Yii::$app->getRequest()->getBodyParams();
        $orderDetail = $params['orderDetail'];
        if($orderDetail){
            $saleDetail = DetalleVenta::find()->where(['venta_id' => $idSale])->all();
            for ($i=0; $i < count($saleDetail); $i++) { 
                $detail = $saleDetail[$i];
                $filterDetail = array_filter($orderDetail, function ($det) use ($detail) {
                    return $det['id']  === $detail['id'];
                });
                if(count($filterDetail) > 0){
                    //actualizar
                    $detail -> cantidad = $filterDetail -> cantidad;
                    if($detail -> save()){

                    }else{
                        return 'error';
                    }
                }else{
                    //eliminar
                    $newSaleDetail = new DetalleVenta();
                    if($detail -> delete()){

                    }else{
                        return 'error';
                    }
                }
            }

            for ($i=0; $i < count($orderDetail); $i++) { 
                $detail = $orderDetail[$i];
                $filterDetail = array_filter($saleDetail, function ($det) use ($detail) {
                    return $det['id']  === $detail['id'];
                });
                if(count($filterDetail) > 0){
                    //actualizar
                }else{
                    //agregar
                    $newSaleDetail = new DetalleVenta();
                    $newSaleDetail -> cantidad = $detail['cantidad'];
                    $newSaleDetail -> producto_id = $detail['id'];
                    $newSaleDetail -> venta_id = $idSale;
                    $newSaleDetail -> estado = 'enviado';
                    if($newSaleDetail -> save()){

                    }else{
                        return $newSaleDetail->errors;
                    }
                }
            }
            $saleDetails = Venta::find()
            ->select(['producto.*', 'detalle_venta.cantidad As cantidad', 'venta.id As idSale', 'detalle_venta.estado as estado'])
            ->where(['venta.id' => $idSale ])
            ->innerJoin('detalle_venta', 'detalle_venta.venta_id=venta.id')
            ->innerJoin('producto', 'producto.id=detalle_venta.producto_id')
            ->asArray()
            ->all();

            $response = [
                'success' => true,
                'message' => 'Pedidos enviados.',
                'saleDetails' => $saleDetails
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'No existen pedidos'
            ];
        }
        return $response;
    }

    public function actionValidateTable($idSale){
        $saleDetails = DetalleVenta::find()->where(['venta_id' => $idSale])->all();
        if(!$saleDetails){
            $sale = Venta::findOne($idSale);
            if($sale -> delete()){
                $table = Mesa::findOne($sale -> mesa_id);
                $table -> estado = 'disponible';
                if($table -> save()){
                    $response = [ 
                        'success' => true,
                        'message' => 'Venta eliminada'
                    ];
                }
            }else{
                $response = [ 
                    'success' => false,
                    'message' => 'Ocurrio un error',
                    'errors' => $sale -> errors
                ];
            }
        }else{
            $response = [ 
                'success' => true,
                'message' => 'Tiene ventas',
                'errors' => $saleDetails
            ];
        }
        return $response;
    }
    public function actionConfirmSale($userId, $idSale)
    {
        /* SI es pedido de app, entonces la venta se carga al ultimo periodo aperturado */
   /*      if($userId === 0 ){
            $period = Periodo::find()
                           ->where(['estado' => true])
                           ->one();
            if($period){
                $userId = $period -> usuario_id;
            }else{
                return  [
                    'success' => false,
                    'message' => 'Ocurrio un error',
                ];
            }
        } */

        $sale = Venta::findOne($idSale);
        $params = Yii::$app->getRequest()->getBodyParams();
        $sale->cantidad_total = intval($params['cantidadTotal']);
        $sale->cantidad_cancelada = $params['cantidadPagada'];
        $sale->usuario_id = $userId;
        $sale->estado = $params['estado'];
        $sale->tipo_pago = $params['tipoPago'];
        $sale->tipo = $params['tipo'];
      
        //$sale->cliente_id = $customerId;
        if ($sale->save()) {
            $table = Mesa::findOne($sale -> mesa_id);
            $table -> estado = 'disponible';
            $table -> save();
            Yii::$app->getResponse()->setStatusCode(201);
            $response = [
                'success' => true,
                'message' => 'Su pedido se realizo exitosamente',
                'sale' => $sale
            ];
        } else {
            Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed.');
            $response = [
                'success' => false,
                'message' => 'failed update',
                'data' => $sale->errors
            ];
        }
        return $response;
    }

}
