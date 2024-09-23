<?php

namespace app\controllers;

use app\models\AreaImpresion;
use app\models\AsignacionImpresora;
use app\models\Cliente;
use app\models\ColaImpresion;
use app\models\DetalleVenta;
use app\models\Mesa;
use app\models\Notificacion;
use app\models\Venta;
use app\models\Periodo;
use app\models\Producto;
use app\models\RegistroGasto;
use app\models\Usuario;
use app\models\VentaAreaImpresion;
use app\models\VentaDescuento;
use Exception;
use Yii;
use yii\db\Query;
use yii\data\Pagination;
use yii\db\Expression;


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
                'orders' => ['get'],
            ]
        ];
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['options']
        ];
        $behaviors['access'] = [
            'class' => \yii\filters\AccessControl::class,
            'only' => ['index', 'orders', 'get-sales', 'get-info-line-chart', 'get-sales-by-day', 'get-sale-detail', 'get-sale-detail-all', 'get-sale-detail-by-period', 'get-products-sale-by-day', 'create-sale', 'update-sale', 'get-total-sale-month'], // acciones a las que se aplicará el control
            'except' => [''],    // acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['index', 'get-sales', 'get-info-line-chart', 'get-sales-by-day', 'get-sale-detail', 'get-sale-detail-all', 'get-sale-detail-by-period', 'get-products-sale-by-day', 'create-sale', 'update-sale', 'get-total-sale-month', 'orders'], // acciones que siguen esta regla
                    'roles' => ['administrador'] // control por roles  permisos
                ],
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['create-sale', 'orders', 'update-sale', 'get-sale-detail-by-period'], // acciones que siguen esta regla
                    'roles' => ['mesero', 'cajero'] // control por roles  permisos
                ],
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['get-sale-detail', 'get-sale-detail-all'], // acciones que siguen esta regla
                    'roles' => ['cajero', 'mesero'] // control por roles  permisos
                ],
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['index'], // acciones que siguen esta regla
                    'roles' => ['monitor'] // control por roles  permisos
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

    public function actionIndex($area)
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        extract($params);

        $query = Venta::find()
            ->select(['venta.*', 'usuario.username', 'area_venta.nombre as area_venta', 'cliente.nombre as customer'])
            ->innerJoin('venta_area_impresion', 'venta.id = venta_area_impresion.venta_id')
            ->innerJoin('usuario', 'usuario.id = venta.usuario_id')
            ->innerJoin('area_venta', 'area_venta.id = venta.area_venta_id')
            ->innerJoin('cliente', 'cliente.id = venta.cliente_id')
            ->where(['area_impresion_id' => $area, 'finalizado' => false])
            ->with(['detalleVentas' => function ($query) use ($area) {
                $query
                    ->select(['detalle_venta.*', 'producto.nombre as nombreProducto', 'producto.area_impresion_id'])
                    ->innerJoin('producto', 'producto.id = detalle_venta.producto_id')
                    ->where(['detalle_venta.detalle_venta_id' => null])
                    ->andWhere([
                        'OR',
                        ['producto.area_impresion_id' => $area], // Incluye productos con el área de impresión actual
                        [
                            'AND',
                            ['detalle_venta.detalle_venta_id' => null],   // Padres sin área de impresión
                            [
                                'EXISTS',
                                (new \yii\db\Query())
                                    ->from('detalle_venta dv2')
                                    ->innerJoin('producto p2', 'p2.id = dv2.producto_id')
                                    ->where('dv2.detalle_venta_id = detalle_venta.id')
                                    ->andWhere(['p2.area_impresion_id' => $area])  // Verifica que tienen al menos un hijo con área de impresión
                            ]
                        ]
                    ])

                    ->with(['detalleVentas' => function ($query) use ($area) {
                        $query
                            ->select(['detalle_venta.*', 'producto.nombre as nombreProducto', 'producto.area_impresion_id'])
                            ->innerJoin('producto', 'producto.id = detalle_venta.producto_id')
                            ->where(['producto.area_impresion_id' => $area]);
                    }])
                   /*  ->orderBy([
                        new \yii\db\Expression('COALESCE(detalle_venta.detalle_venta_id, detalle_venta.id) DESC'), // Ordena por el detalle principal o su propio ID si es un producto principal
                        new \yii\db\Expression('detalle_venta.detalle_venta_id IS NOT NULL ASC'),   // Asegura que los productos principales se muestren antes que sus modificadores
                        'detalle_venta.id' => SORT_DESC, // Ordena por ID para mantener el orden de inserción
                    ])  */
                    ->orderBy(['id' => SORT_DESC])
                    ->asArray();
            }])
            ->orderBy(['venta.id' => SORT_DESC])
            ->asArray()
            ->all();

        $response = [
            "success" => true,
            'message' => 'Lista de pedidos',
            'orders' => $query
        ];
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
                ->where(['>=', 'fecha', $params['fechaInicio']])
                ->andWhere(['<=', 'fecha', $fechaFinWhole])
                ->andWhere(['venta.estado' => 'pagado'])
                ->groupBy(['mes'])
                ->orderBy(['mes' => SORT_ASC])
                ->all();
        } else {
            $salesForDay = Venta::find()
                ->select(['DATE(fecha) AS fecha', 'SUM(cantidad_total) AS total'])
                ->where(['>=', 'fecha', $params['fechaInicio']])
                ->andWhere(['<=', 'fecha', $fechaFinWhole])
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
  

    /* Retorna la venta con su detalle de venta, segun un rango de fecha y usuario */
    public function actionGetSaleDetail($pageSize = 7)
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $user = isset($params['usuarioId']) ? $params['usuarioId'] : null;
        $state = isset($params['estado']) ? $params['estado'] : null;
        $area = isset($params['area_venta_id']) ? $params['area_venta_id'] : null;
        $fechaIni = isset($params['fechaInicio']) ? $params['fechaInicio'] : null;
        $fechaFin = isset($params['fechaFin']) ? $params['fechaFin'] . ' 23:59:58.0000' : null;
        $query = Venta::find()
            ->select(['venta.*', 'usuario.username', 'mesa.nombre as mesa', 'cliente.nombre as customer', 'area_venta.nombre as area', 'tipo_pago.descripcion as tipo_pago'])
            ->innerJoin('usuario', 'usuario.id = venta.usuario_id')
            ->leftJoin('mesa', 'mesa.id=venta.mesa_id')
            ->innerJoin('cliente', 'cliente.id=venta.cliente_id')
            ->innerJoin('area_venta', 'area_venta.id=venta.area_venta_id')
            ->leftJoin('tipo_pago', 'tipo_pago.id=venta.tipo_pago_id')
            ->filterWhere(['>=', 'fecha', $fechaIni])
            ->andFilterWhere(['<=', 'fecha', $fechaFin])
            ->andFilterWhere(['usuario_id' => $user])
            ->andFilterWhere(['venta.estado' => $state])
            ->andFilterWhere(['venta.area_venta_id' => $area])
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

    public function actionCancelSale($idSale)
    {
        $sale = Venta::findOne($idSale);
        if ($sale) {
            $db = Yii::$app->db;
            $transaction = $db->beginTransaction();
            try {
                $orderDetail = DetalleVenta::find()->where(['venta_id' => $idSale])->all();
                for ($i = 0; $i < count($orderDetail); $i++) {
                    $order  = $orderDetail[$i];
                    $order->estado = 'cancelado';
                    if (!$order->save()) {
                        throw new Exception('No se pudo actualizar el pedido');
                        return;
                    }
                }
                if($sale -> mesa_id){
                    $table = Mesa::findOne($sale->mesa_id);
                    $table->estado = 'disponible';
                    if (!$table->save()) {
                        throw new Exception('No se pudo actualizar la mesa');
                    }
                }
                $sale->estado = 'cancelado';
                if ($sale->save()) {
                    $response = [
                        'success' => true,
                        'message' => 'Venta cancelada'
                    ];
                }
                $transaction->commit();
            } catch (Exception $e) {
                $response = [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'Ocurrio un error'
            ];
        }
        return $response;
    }

    public function actionUpdateState($state, $idSale)
    {
        $sale = Venta::findOne($idSale);
        $sale->estado = $state;
        if ($sale->save()) {
            $response = [
                'success' => true,
                'message' => 'Actualizado corretamente'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Ucurrio un error'
            ];
        }
        return $response;
    }

    /* Cantidad de productos por dia */
    public function actionGetProductsSaleByDay()
    {
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
            ->andWhere(["<>", 'detalle_venta.estado', 'cancelado'])
            ->groupBy(['producto.nombre', 'producto.id'])
            ->orderBy(['cantidad_vendida' => SORT_DESC]);
        if ($query) {
            $response = [
                'success' => true,
                'message' => 'Lista de ventas de productos por dia',
                'reports' => $query->all()
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'No existen reportes.',
                'reports' => []
            ];
        }
        return $response;
    }
    public function actionCreateSale()
    {
        $sale = new Venta();
        $params = Yii::$app->getRequest()->getBodyParams();
        $sale->load($params, '');
        date_default_timezone_set('America/La_Paz');
        $sale->fecha = date('Y-m-d H:i:s');
        $numberOrder = Venta::find()->all();
        $sale->numero_pedido = count($numberOrder) + 1;
        if ($sale->save()) {
            //actulalizar estado de la mesa
            $table = Mesa::findOne($sale->mesa_id);
            $table->estado = 'ocupado';
            $table->save();
            $response = [
                'success' => true,
                'message' => 'Venta creada venta',
                'sale' => $sale
            ];
        } else {
            $response = [
                'success' => true,
                'message' => 'Existen errores en los parametros',
            ];
        }
        return $response;
    }

    public function actionGetInformationSale($idTable = null, $idSale = null)
    {
        $state = 'consumiendo';
        if($idSale){
            $state = null;
        }
        $sale = Venta::find()->select(['venta.*', 'usuario.username as usuario', 'mesa.nombre as mesa'])
            ->filterWhere(['venta.estado' => $state])
            ->andFilterWhere(['mesa_id' => $idTable])
            ->andFilterWhere(['venta.id' => $idSale])
            ->innerJoin('usuario', 'usuario.id=venta.usuario_id')
            ->leftJoin('mesa', 'mesa.id=venta.mesa_id')
            ->asArray()
            ->one();

        if ($sale) {
            $saleDetailFull = DetalleVenta::find()
                ->select(['detalle_venta.*', 'producto.nombre', 'producto.precio_venta', 'producto.costo_compra', 'producto.area_impresion_id'])
                ->where(['venta_id' => $sale['id'], 'detalle_venta_id' => null])
                ->andWhere(['<>', 'detalle_venta.estado', 'cancelado'])
                ->innerJoin('producto', 'producto.id=detalle_venta.producto_id')
                ->orderBy(['id' => SORT_DESC])
                ->with(['detalleVentas' => function ($query) {
                    $query->select(['detalle_venta.*', 'producto.nombre', 'producto.area_impresion_id'])
                        ->innerJoin('producto', 'producto.id=detalle_venta.producto_id');
                }])
                ->asArray()
                ->all();
            $customer = Cliente::findOne($sale['cliente_id']);
            $discount = VentaDescuento::find()->where(['venta_id' => $sale['id']])->one();
            $response = [
                'success' => true,
                'message' => 'Info de venta',
                'saleDetails' => $saleDetailFull,
                'sale' => $sale,
                'customer' => $customer,
                'discount' => $discount
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'No existe venta asociada a la mesa',
                'saleDetails' => []
            ];
        }
        return $response;
    }

    public function actionFastSale()
    {
        //CREAR VENTA, CREAR DETALLE DE VENTA (inventario, estado, etc)
        $params = Yii::$app->getRequest()->getBodyParams();
        $errors = [];
        $transaction = Yii::$app->db->beginTransaction();
        try {
            /* Crear venta */
            $sale = $this->createSale($params);
            $sale->load($params, '');
            if ($sale->save()) {
                /* Agregar productos */
                $orderDetails = $params['orderDetail'];
                foreach ($orderDetails as $order) {
                    $res = $this->addNewOrderDetail($order, $sale->id, $params['printed'], $order['cantidad']);
                    if ($res) $errors[] = $res;
                }

                if (count($params['printerAreasIds']) > 0) {
                    for ($i = 0; $i < count($params['printerAreasIds']); $i++) {
                        $this->assignSaleToArea($sale->id, $params['printerAreasIds'][$i]);
                        if (!$params['printed']) {
                            $this->createPrintSpooler($sale->id, $params['printerAreasIds'][$i]);
                        }
                    }
                }

                if(!$params['printed']){
                    $printerCaja = AreaImpresion::find()
                    ->where(['nombre' => 'Caja'])
                    ->one();
                    if($printerCaja)$this->createPrintSpooler($sale -> id, $printerCaja->id);
                }
                /* Agregar descuento     */
                $discount = $params['discount'];
                if ($discount['valor'] > 0) {
                    if(!$discount['tipo_descuento_id']){
                        $discount['tipo_descuento_id'] = 1;
                    }
                    $model = new VentaDescuento();
                    $model->load($discount, '');
                    $model->venta_id = $sale->id;
                    if (!$model->save()) {
                        throw new Exception(json_encode($model->errors));
                    }
                }
            } else {
                throw new Exception(json_encode($sale->errors));
            }
            $user = Usuario::findOne($sale->usuario_id);
            $response = [
                'success' => true,
                'message' => 'Venta creada',
                'data' => [
                    ...$sale,
                    'usuario' => $user -> username,
                ],
            ];
            $transaction->commit();
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed.');
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
        return $response;
    }

    private function addNewOrderDetail($detail, $idSale, $printed, $quantity)
    {
        try {
            $saleDetailParent = $this->createAndSaveSaleDetail($detail, $idSale, $printed, abs($quantity), $quantity > 0, null);

            if (isset($detail['detalleVentas'])) {
                foreach ($detail['detalleVentas'] as $modifier) {
                    $quantityDetail = $modifier['cantidad'];
                    if(isset($modifier['detalle_venta_id'])){
                        $model = DetalleVenta::findOne($modifier['id']);
                        $quantityDetail = $model->cantidad - $quantityDetail;
                    }

                    $this->createAndSaveSaleDetail($modifier,  $idSale, $printed, $quantityDetail, $quantity > 0, $saleDetailParent->id);
                }
            }
            return false; // Indicar que no hubo errores.
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    private function createAndSaveSaleDetail($detail, $saleId, $printed, $quantity,  $isSent, $idParent)
    {
        $saleDetail = new DetalleVenta();
        $saleDetail->load($detail, '');
        $saleDetail->cantidad = $quantity;
        $saleDetail->venta_id = $saleId;
        $saleDetail->estado = $isSent ? 'enviado' : 'cancelado';
        $saleDetail->impreso = $printed;
        $saleDetail->detalle_venta_id = $idParent;
        if (!$saleDetail->save()) {
            throw new Exception('Ocurrió un error, intente de nuevo' . json_encode($saleDetail->errors));
        }
        return $saleDetail;
    }

    private function createPrintSpooler($idSale, $idArea)
    {
        //Si existe el area pero no tiene impresora
        $printer = AsignacionImpresora::find()->where(['area_impresion_id' => $idArea])->one();
        if (!$printer)return;

        $printerSpooler = new ColaImpresion();
        $printerSpooler->venta_id = $idSale;
        $printerSpooler->estado = false;
        $printerSpooler->area_impresion_id = $idArea;
        if (!$printerSpooler->save()) {
            throw new Exception('Ocurrio un error, intente de nuevo' . json_encode($printerSpooler->errors));
        }
    }
    private function createSale($params)
    {
        $sale = new Venta();
        $sale->load($params, '');
        $sale->estado = 'consumiendo';
        date_default_timezone_set('America/La_Paz');
        $sale->fecha = date('Y-m-d H:i:s');
        $numberOrder = Venta::find()->all();
        $sale->numero_pedido = count($numberOrder) + 1;
        $sale->save();

        if (isset($params['mesa_id'])) {
            $table = Mesa::findOne($params['mesa_id']);
            $table->estado = 'ocupado';
            $table->save();
        }
        return $sale;
    }


    private function assignSaleToArea($idSale, $idArea)
    {
        //si existe update false, si no existe crear
        $model = VentaAreaImpresion::find()
            ->where(['venta_id' => $idSale, 'area_impresion_id' => $idArea])
            ->one();

        if (!$model) {
            $model = new VentaAreaImpresion();
            $model->venta_id = $idSale;
            $model->area_impresion_id = $idArea;
        }
        $model->finalizado = false;
        if (!$model->save()) {
            throw new Exception(json_encode($model->errors));
        }
    }
    private function createNotification($msm)
    {
        $notification = new Notificacion();
        $notification->mensaje = $msm;
        $notification->leido = false;
        date_default_timezone_set('America/La_Paz');
        $notification->create_ts = date('Y-m-d H:i:s');
        $notification->save();
    }
    public function actionUpdateSaleImproved($idSale)
    {
        $idSale = isset($idSale) ? $idSale : null;
        $params = Yii::$app->getRequest()->getBodyParams();
        try {
            if (!$idSale) {
                $sale = $this->createSale($params);
            } else {
                $sale = Venta::findOne($idSale);
            }
            $orderDetail = $params['orderDetail']; //actualizado con estado nuevo/enviado/enviado-impresora
            //nota y cliente
            $sale->cliente_id = $params['cliente_id'];
            $sale->nota = $params['note'];
            $sale->save();

            $saleDetail = DetalleVenta::find()
                ->where(['venta_id' => $sale->id])
                ->andWhere(['<>', 'estado', 'cancelado'])
                ->all();

            //Si el cliente es distinto de COMPUTADORA, se crea la cola de impresion
            if (count($params['printerAreasIds']) > 0) {
                for ($i = 0; $i < count($params['printerAreasIds']); $i++) {
                    $this->assignSaleToArea($sale->id, $params['printerAreasIds'][$i]);
                    if (!$params['printed']) {
                        $this->createPrintSpooler($sale->id, $params['printerAreasIds'][$i]);
                    }
                }
            }
            $errors = [];
            for ($i = 0; $i < count($orderDetail); $i++) {
                $detail = $orderDetail[$i];

                if ($detail["estado"] === 'nuevo') {
                    $res = $this->addNewOrderDetail($detail, $sale->id, $params['printed'], $detail['cantidad']);
                    if ($res) $errors[] = $res;
                    $sale->save();
                    continue;
                }


                if ($detail["estado"] === 'cancelado') {
                    DetalleVenta::updateAll(['estado' => 'cancelado'], ['or', 'id = :id', 'detalle_venta_id = :id'], [':id' => $detail["id"]]);
                    //DetalleVenta::updateAll(['estado' => 'cancelado'], ['or', 'id' => $detail["id"], 'detalle_venta_id' => $detail["id"]]);
                    $this->createNotification("Se cancelo el producto: " . $detail["nombre"] . " del pedido #" . $sale->numero_pedido . " por el usuario: " . $sale->usuario_id);
                } else {
                    $filterDetail = array_filter($saleDetail, function ($det) use ($detail) {
                        return $det['id']  === $detail['id'] && $det['estado'] === $detail['estado'];
                    });
                    //va a entrar cuando el: pedido 3 -> tiene el mismo id y mismo estado;
                    if (count($filterDetail) > 0) {
                        $filterDetailValues = array_values($filterDetail);

                        //Si no es igual, es que se ha decrementado o incrementado.
                        if ($filterDetailValues[0]['cantidad'] !== $detail["cantidad"]) {
                            $difference = $detail["cantidad"] - $filterDetailValues[0]['cantidad'];
                        
                            //si es mayor se crea otro detalle de venta positivo, si es negativo se reduce la cantidad del detalle original y se crear un detalle de venta con estado cancelado con la diferencia.

                            $res = $this->addNewOrderDetail($detail, $sale->id, $params['printed'], $difference);
                            if ($difference < 0) {
                                DetalleVenta::updateAll(['cantidad' => $detail['cantidad']], ['id' => $detail["id"]]);
                                if( count($detail['detalleVentas']) > 0 ){
                                    $this -> updateDetail($detail['detalleVentas']);
                                }
                            }
                            
                            if ($res) $errors[] = $res;

                            $sale->save();
                        }
                    }
                }
            }

            $saleDetailFull = DetalleVenta::find()
                ->select(['detalle_venta.*', 'producto.nombre', 'producto.area_impresion_id'])
                ->innerJoin('producto', 'producto.id=detalle_venta.producto_id')
                ->where(['venta_id' => $sale->id, 'detalle_venta_id' => null])
                ->andWhere(['<>', 'detalle_venta.estado', 'cancelado'])
                ->orderBy(['id' => SORT_DESC])
                ->with(['detalleVentas' => function ($query) {
                    $query->select(['detalle_venta.*', 'producto.nombre', 'producto.area_impresion_id'])
                        ->innerJoin('producto', 'producto.id=detalle_venta.producto_id');
                }])
                ->asArray()
                ->all();
            $customer = Cliente::findOne($params['cliente_id']);
            $user = Usuario::findOne($sale->usuario_id);
            $response = [
                'success' => true,
                'message' => 'Pedidos enviados.',
                'orderDetailCurrently' => $saleDetailFull,
                'sale' => [...$sale, 'customer' => $customer, 'usuario' => $user -> username],
                'errors' => $errors

            ];
        } catch (Exception $e) {
            $response = [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
        return $response;
    }

    private function updateDetail($detail){
        foreach ($detail as $key => $value) {
            DetalleVenta::updateAll(['cantidad' => $value['cantidad']], ['id' => $value['id']]);
        }
    }

    public function actionConfirmSale($idSale)
    {
        $sale = Venta::findOne($idSale);
        $params = Yii::$app->getRequest()->getBodyParams();
        $sale->load($params, '');
        $sale->fecha_cierre = date('Y-m-d H:i:s');
        $db = Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            if (!$params['printed'] && !$params['isEdit']){
                $printerCaja = AreaImpresion::find()
                                            ->where(['nombre' => 'Caja'])
                                            ->one();
                if($printerCaja)$this->createPrintSpooler($idSale, $printerCaja->id);
            }

            if ($sale->save()) {
                if($sale -> mesa_id && !$params['isEdit']){
                    $table = Mesa::findOne($sale->mesa_id);
                    $table->estado = 'disponible';
                    if (!$table->save()) {
                        throw new Exception('Error al actualizar mesa, intente nuevamente.');
                    }
                }

                /* Agregar descuento */
                $discount = $params['discount'];
                if ($discount['valor'] > 0 || $params['isEdit']) {
                    $model = new VentaDescuento();

                    if(isset($discount['id'])){
                        $discountModel = VentaDescuento::findOne($discount['id']);
                        if($discount['valor'] !== $discountModel->valor){
                            //eliminar y crar uno nuevo, crear notifiacion
                            $message = 'Se ha modificado el descuento ' . $discountModel['valor'] . ' por el valor ' . $discount['valor'] . ' del pedido #' . $sale->numero_pedido . ' por el usuario: ' . $sale->usuario_id;
                            $this->createNotification($message);

                            if($discount['valor'] !== 0){
                                $model = $discountModel; 
                            }else{
                                $discountModel -> delete();
                            }
                        }
                    }

                    if(!$discount['tipo_descuento_id']){
                        $discount['tipo_descuento_id'] = 1;
                    }

                    $model->load($discount, '');
                    $model->venta_id = $sale->id;

                    if (!$model->save()) {
                        throw new Exception(json_encode($model->errors));
                    }
                }

                Yii::$app->getResponse()->setStatusCode(201);
                $response = [
                    'success' => true,
                    'message' => 'Venta cerrada exitosamente',
                    'sale' => $sale
                ];
                $transaction->commit();
            } else {
                throw new Exception('Error al actualizar la venta, intente nuevamente.');
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed.');
            $response = [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
        return $response;
    }

    public function actionServe($idSale, $idArea)
    {
        $salePrinterArea = VentaAreaImpresion::find()
            ->where(['area_impresion_id' => $idArea, 'venta_id' => $idSale])
            ->one();

        if ($salePrinterArea) {
            $salePrinterArea->finalizado = true;
            if($salePrinterArea->save()){
                $response = [
                    'success' => true,
                    'message' => 'Venta actualizada'
                ];
            }else{
                $response = [
                    'success' => false,
                    'message' => 'Error al actualizar la venta'
                ];
            }
        }
     
        return $response;
    }


    public function actionChangeStateOrderDetails($idSale, $idPrinterArea)
    {
        //$type = $type === 'kitchen' ? 'comida' : 'bebida';
        $params = Yii::$app->getRequest()->getBodyParams();
        $state = $params['state'];

        $states = [
            'enviado' => 'preparando',
            'preparando' => 'listo',
            'listo' => 'listo'
        ];
        $newState = $states[$state];
        DetalleVenta::updateAll(
            ['estado' => $newState], // Define el nuevo estado según tus necesidades
            ['and',
                ['venta_id' => $idSale],
                ['estado' => $state],
                ['exists', (new \yii\db\Query())
                    ->select('id')
                    ->from('producto')
                    ->where('producto.id = detalle_venta.producto_id')
                    ->andWhere(['producto.area_impresion_id' => $idPrinterArea])
                ]
            ]
        );
        
        $response = [
            'success' => true,
            'message' => 'Pedidos actualizados',

        ];
        return $response;
    }

    public function actionChangeStateSampleOrder($idSaleDetail)
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $saleDetail = DetalleVenta::findOne($idSaleDetail);
        if ($saleDetail) {
            if($saleDetail -> detalle_venta_id){
                //si tiene un parent
                $saleDetailParent = DetalleVenta::findOne($saleDetail -> detalle_venta_id);
                $saleDetailParent->estado = $params['state'];
                $saleDetailParent->save();
            }else{
                //si tiene hijos
                DetalleVenta::updateAll(['estado' => $params['state']], ['detalle_venta_id' => $saleDetail["id"]]);
            }
            $saleDetail->estado = $params['state'];
            if ($saleDetail->save()) {
                $response = [
                    'success' => true,
                    'message' => 'Pedidos actualizados'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Existen parametros en los parametros'
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'No existen detalle de venta'
            ];
        }

        return $response;
    }

   

    public function actionChangeTable($idTableOld, $idTableNew)
    {
        $sale = Venta::find()
            ->where(['mesa_id' => $idTableOld, 'venta.estado' => 'consumiendo'])
            ->one();
        if ($sale) {
            $sale->mesa_id = $idTableNew;
            $tableNew = Mesa::findOne($idTableNew);
            $tableOld = Mesa::findOne($idTableOld);
            $tableNew->estado = 'ocupado';
            $tableOld->estado = 'disponible';
            if ($sale->save() && $tableNew->save() && $tableOld->save()) {
                $response = [
                    'success' => true,
                    'message' => 'Cambio exitoso'
                ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Ocurrio un error'
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'No hay pedidos'
            ];
        }
        return $response;
    }

    public function actionOrders($idUser, $idPeriod)
    {
        $period = Periodo::findOne($idPeriod);

        $orders = Usuario::find()
            ->select(['detalle_venta.id', 'venta.numero_pedido', 'mesa.nombre as mesa', 'detalle_venta.cantidad', 'detalle_venta.estado', 'producto.nombre', 'detalle_venta.create_ts'])
            ->where(['id' => $idUser])
            ->innerJoin('venta', 'venta.usuario_id = usuario.id')
            ->innerJoin('mesa', 'mesa.id = venta.mesa_id')
            ->innerJoin('detalle_venta', 'detalle_venta.venta_id = venta.id')
            ->where(['<>', 'detalle_venta.estado', 'entregado'])
            ->andWhere(['venta.usuario_id' => $idUser])
            ->andWhere(['>=', 'fecha', $period->fecha_inicio])
            ->innerJoin('producto', 'producto.id = detalle_venta.producto_id')
            ->asArray()
            ->orderBy(['create_ts' => SORT_DESC])
            ->all();

        $response = [
            'success' => true,
            'orders' => $orders
        ];

        return $response;
    }

    public function actionConfirmCredit($idSale){
        $sale = Venta::findOne($idSale);
        $sale->estado = 'pagado';
        if($sale->save()){
            $response = [
                'success' => true,
                'message' => 'Pago exitoso'
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'Ocurrio un error'
            ];
        }

        return $response;
    }
}
