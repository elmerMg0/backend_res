<?php

namespace app\controllers;

use app\models\Cliente;
use app\models\DetalleVenta;
use app\models\LogVenta;
use app\models\Mesa;
use app\models\Venta;
use app\models\Periodo;
use app\models\Producto;
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
                'get-sales' => ['get'],
                'get-info-line-chart' => ['post'],
                'get-sales-by-day' => ['post'],
                'get-sale-detail' => ['post'],
                'get-sale-detail-all' => ['get'],
                'get-sale-detail-by-period' => ['post'],
                'get-products-sale-by-day' => ['post'],
                'create-sale' => ['post'],
                'update-sale' => ['post'],
            ]
        ];
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['options']
        ];
        $behaviors['access'] = [
            'class' => \yii\filters\AccessControl::class,
            'only' => ['get-sales', 'get-info-line-chart', 'get-sales-by-day', 'get-sale-detail','get-sale-detail-all','get-sale-detail-by-period', 'get-products-sale-by-day', 'create-sale', 'update-sale'], // acciones a las que se aplicará el control
            'except' => [''],    // acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['get-sales', 'get-info-line-chart', 'get-sales-by-day', 'get-sale-detail','get-sale-detail-all', 'get-sale-detail-by-period', 'get-products-sale-by-day', 'create-sale', 'update-sale'], // acciones que siguen esta regla
                    'roles' => ['administrador'] // control por roles  permisos
                ],
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['create-sale', 'update-sale','get-sale-detail-by-period', 'get-'], // acciones que siguen esta regla
                    'roles' => ['cajero'] // control por roles  permisos
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
        $sales = Venta::find()
                        ->select(['venta.*', 'usuario.username', 'mesa.nombre as nroMesa'])
                        ->where(['finalizado' => false])
                        ->innerJoin('usuario', 'usuario.id= venta.usuario_id')
                        ->innerJoin('mesa', 'mesa.id= venta.mesa_id')
                        ->with('detalleVentas', 'detalleVentas.producto')
                        ->orderBy(['id' => SORT_DESC])
                        ->asArray()
                        ->all();

        $response = [
            "success" => true,
            'message' => 'Lista de pedidos',
            'orders' => $sales 
        ];
        return $response;
    }
    public function actionCreate($userId=0)
    {
        /* SI es pedido de app, entonces la venta se carga al ultimo periodo aperturado */
     /*    if($userId === 0 ){
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
 */
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
        $sale->cliente_id = $params['cliente_id'];
        $sale->mesa_id = $params['mesa_id'];
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
                $saleDetail->estado =  'enviado';
                
                $product = Producto::findOne($order['id']);
                if($product -> tipo === 'bebida'){
                    /*Si es bebida validar el stock */
                    $total = $product -> stock - $order['cantidad'];
                    $product -> stock = $total;
                }
                if ($saleDetail->save() && $product -> save()) {
                   
                }else{
                    Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed.');
                    return $response = [
                        'success' => false,
                        'message' => 'Existen errores en los parametros',
                        'errors' => $saleDetail->errors
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
                'errors' => $sale->errors
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
                ->select(['venta.*', 'usuario.username', 'mesa.nombre as mesa'])
                ->innerJoin('usuario','usuario.id = venta.usuario_id')
                ->innerJoin('mesa', 'mesa.id=venta.mesa_id')
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
        ->with('cliente')
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
                'message' => 'No existen ventas delivery app',
                'sales' => []
            ];
        }
        return $response;
    }

    public function actionGetSaleDetailByPeriod ($pageSize = 7) {
        $params = Yii::$app->getRequest()->getBodyParams();
        $period = Periodo::findOne($params['idPeriod']);
        $query = Venta::find()
                ->select(['venta.*', 'usuario.username', 'mesa.nombre as mesa', 'cliente.nombre as cliente'])
                ->join('LEFT JOIN', 'usuario','usuario.id = venta.usuario_id')
                ->innerJoin('mesa', 'mesa.id=venta.mesa_id')
                ->innerJoin('cliente', 'cliente.id=venta.cliente_id')
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
            $orderDetail = DetalleVenta::find()->where(['venta_id' => $idSale])->all();
            
            for($i = 0; $i < count($orderDetail); $i++){
                $order  = $orderDetail[$i];
                $product = Producto::findOne($order->producto_id);
                if($product -> tipo === 'bebida'){
                    $product -> stock = $product -> stock + $order -> cantidad;
                    $product -> save();
                }
            }


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
    public function actionCreateSale(){
        $sale = new Venta();
        $params = Yii::$app->getRequest()->getBodyParams();
        $sale -> load($params, '');
        date_default_timezone_set('America/La_Paz');
        $sale -> fecha = date('Y-m-d H:i:s');
        $numberOrder = Venta::find()->all();
        $sale->numero_pedido = count($numberOrder) + 1;
        if($sale -> save()){
            //actulalizar estado de la mesa
            $table = Mesa::findOne($sale -> mesa_id);
            $table -> estado = 'ocupado';
            $table -> save();
            $response = [
                'success' => true,
                'message' => 'Venta creada venta',
                'sale' => $sale
            ];
        }else{
            $response = [
                'success' => true,
                'message' => 'Existen errores en los parametros',
            ];
        }
        return $response;
    }

    public function actionGetInformationSale($idTable){
        $sale = Venta::find()->select(['venta.*', 'usuario.nombres as usuario'])
            ->where(['mesa_id' => $idTable, 'venta.estado' => 'consumiendo' ])
            ->innerJoin('usuario', 'usuario.id=venta.usuario_id')
            ->asArray()
            -> one();
        if( $sale ){
            $saleDetails = Venta::find()
            ->select(['producto.*', 'detalle_venta.cantidad As cantidad', 'venta.id As idSale', 'detalle_venta.estado as estado'])
            ->where(['mesa_id' => $idTable, 'venta.estado' => 'consumiendo' ])
            ->innerJoin('detalle_venta', 'detalle_venta.venta_id=venta.id')
            ->innerJoin('producto', 'producto.id=detalle_venta.producto_id')
            ->asArray()
            ->all();

            $saleDetailFull = DetalleVenta::find()
                    ->select(['detalle_venta.*', 'producto.nombre', 'producto.stock', 'producto.precio_venta', 'producto.precio_compra'
                                ,'producto.tipo'])
                    ->where(['venta_id' => $sale['id']])
                    ->innerJoin('producto', 'producto.id=detalle_venta.producto_id')
                    ->orderBy(['id' => SORT_DESC])
                    ->asArray()
                    ->all();
            $customer = Cliente::findOne($sale['cliente_id']);
            $response = [
                'success' => true,
                'message' => 'Info de venta',
                'saleDetails' => $saleDetailFull,
                'sale' => $sale,
                'customer' => $customer
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'No existe venta asociada a la mesa',
                'saleDetails' => []
            ];
        }
        return $response;
    }


    private function addNewOrderDetail( $detail, $idSale, $printout, $amount){
        $newSaleDetail = new DetalleVenta();
        $newSaleDetail -> cantidad = abs($amount);
        $newSaleDetail -> producto_id = $detail['producto_id'];
        $newSaleDetail -> venta_id = $idSale;
        $newSaleDetail -> estado = $amount > 0 ? 'enviado' : 'cancelado';
        $newSaleDetail -> impreso = $printout === 'windows' ? true : false;

        $product =  Producto::findOne($detail['producto_id']);
        if($product -> tipo === 'bebida'){
            $product -> stock = $product -> stock - $detail ['cantidad'];
            $product -> save();
        }
        $newSaleDetail -> save();
    }

    public function actionUpdateSaleImproved($idSale){
        $params = Yii::$app->getRequest()->getBodyParams();
        $orderDetail = $params['orderDetail']; //actualizado con estado nuevo/enviado/enviado-impresora
        //nota y cliente
        $sale = Venta::findOne($idSale);
        $sale -> cliente_id = $params['cliente_id'];
        $sale -> nota = $params['note'];
        $sale -> save();

        $saleDetail = DetalleVenta::find()
                    ->where(['venta_id' => $idSale])
                    ->andWhere(['<>', 'estado', 'cancelado'])
                    ->all(); 

        for($i = 0; $i < count($orderDetail); $i++){
            $detail = $orderDetail[$i];

            if($detail ["estado"] === 'nuevo'){
                $this->addNewOrderDetail($detail, $idSale, $params['userAgent'], $detail['cantidad']);
                $sale -> finalizado = false;
                $sale -> save();
            } 

            if($detail ["estado"] === 'cancelado'){
                DetalleVenta::updateAll(['estado' => 'cancelado'], ['id' => $detail["id"]]);
            }else{
                $filterDetail = array_filter($saleDetail, function ($det) use ($detail) {
                    return $det['id']  === $detail['id'] && $det['estado'] === $detail['estado'];
                });
                //nunca va a entrar poque los pedidos siempre seran nuevos.
                //va a entrar cuando em: pedodo 3 -> tiene el mismo id y mismo estado;
                if(count($filterDetail) > 0){
                    //actualizar  dividir el detalle de venta.
                    //detail  $filterDetail 
                    $filterDetailValues = array_values($filterDetail);
                    if($filterDetailValues[0]['cantidad'] !== $detail["cantidad"]){
                        //Si no es igual, es que se ha decrementado o incrementado.
                        $difference = $detail["cantidad"] - $filterDetailValues[0]['cantidad'];
                        if($difference < 0){
                            DetalleVenta::updateAll(['cantidad' => $detail['cantidad']], ['id' => $detail["id"]]);
                        }
                        $this->addNewOrderDetail($detail, $idSale, $params['userAgent'], $difference);
                        $sale -> finalizado = false;
                        $sale -> save();
                    }
                }
            }
        }

        $saleDetailFull = DetalleVenta::find()
                    ->select(['detalle_venta.*', 'producto.nombre', 'producto.stock', 'producto.precio_venta', 'producto.precio_compra'
                    ,'producto.tipo'])
                    ->where(['venta_id' => $idSale])
                    ->innerJoin('producto', 'producto.id=detalle_venta.producto_id')
                    ->asArray()
                    ->orderBy(['id' => SORT_DESC])
                    ->all();  
        $response = [
            'success' => true,
            'message' => 'Pedidos enviados.',
            'orderDetailCurrently' => $saleDetailFull

        ];
    return $response;
}

    public function actionConfirmSale($idSale)
    {
        $sale = Venta::findOne($idSale);
        $params = Yii::$app->getRequest()->getBodyParams();
        $sale -> load($params, '');
      
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

    public function actionServe($idSale){
        $sale = Venta::findOne($idSale);
        if( $sale ){
            $sale -> finalizado = true;
            if( $sale -> save()){
                $response = [
                    'success' => true,
                    'message' => 'Venta servida'
                ];
            }else{
                $response = [
                    'success' => false,
                    'message' => 'existen parametros incorrectos',
                    'errors' => $sale -> errors
                ];
            }
        }else{
            $response = [
                'success' => false,
                'message' => 'No existe la venta',
            ];
        }
        return $response;
    }


    public function actionChangeStateOrderDetails($idSale){

        $params = Yii::$app->getRequest()->getBodyParams();
        $state = $params['state'];
        $orderDetail = DetalleVenta::find()
                            ->where(['venta_id' => $idSale, 'estado' => $state])
                            ->all();
        if($orderDetail){
            $states = [
                'enviado' => 'preparando',
                'preparando' => 'listo',
                'listo' => 'listo'
            ];

            $newState = $states[$state];
            DetalleVenta::updateAll(['estado' => $newState], ['venta_id' => $idSale, 'estado' => $state]);
            
            $response = [
                'success' => true,
                'message' => 'Pedidos actualizados'
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'No existen pedidos'
            ];
        }
        return $response;
    }

    public function actionChangeStateSampleOrder($idSaleDetail){
        $SaleDetail = DetalleVenta::findOne($idSaleDetail);
        $params = Yii::$app -> getRequest() -> getBodyParams();
        if($SaleDetail){
            $SaleDetail -> estado = $params['state'];
            if($SaleDetail -> save()){
            $response = [
                'success' => true,
                'message' => 'Pedidos actualizados'
            ];
            }else{
                $response = [
                    'success' => false,
                    'message' => 'Existen parametros en los parametros'
                ];
            }
        }else{
            $response = [
                'success' => false,
                'message' => 'No existen detalle de venta'
            ];
        }

        return $response;
    }

}
