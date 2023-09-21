<?php

namespace app\controllers;

use Yii;
use app\models\Periodo;
use app\models\Usuario;
use app\models\Venta;

class PeriodoController extends \yii\web\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'start-period' => ['POST'],
                'close-period' => ['POST'],
                'get-detail-period' => ['GET'],
                'get-detail-sale-by-user' => ['GET']
            ]   
        ];

        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['options']
        ];

        $behaviors['access'] = [
            'class' => \yii\filters\AccessControl::class,
            'only' => ['get-detail-period','start-period', 'close-period'], // acciones a las que se aplicará el control
            'except' => [''],    // acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['get-detail-period', 'start-period', 'close-period', 'get-detail-sale-by-user'], // acciones que siguen esta regla
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

    public function actionStartPeriod($userId)
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $period = new Periodo();
        date_default_timezone_set('America/La_Paz');
        $period->fecha_inicio = date('Y-m-d H:i:s');
        $period->estado = true;
        $period->caja_inicial = $params['cajaInicial'];
        $period->usuario_id = $userId;
        if ($period->save()) {
            $response = [
                'success' => true,
                'message' => 'Periodo iniciado con exito!',
                'period' => $period,
                'info' => [
                    'fechaInicio' => $period->fecha_inicio,
                    'cajaInicial' => $period->caja_inicial,
                    'totalSaleCash' => 0,
                    'totalSaleCard' =>  0,
                    'totalSaleTransfer' => 0,
                    'totalSale' => 0,
                    'totalSaleApp' => 0
                    ]   
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Existen parametros incorrectos',
                'errors' => $period->errors
            ];
        }

        return $response;
    }
    public function actionClosePeriod( $idPeriod, $idUser)
    {
        $user = Usuario::findOne($idUser);

        $params = Yii::$app->getRequest()->getBodyParams();
        $period = Periodo::findOne($idPeriod);
         date_default_timezone_set('America/La_Paz');
        $period -> fecha_fin = date('Y-m-d H:i:s');
        $period->estado = false;

        $totalSale = Venta::find()
        ->where(['>=', 'fecha', $period->fecha_inicio])
        ->andWhere(['usuario_id' => $user->id])
        ->sum('cantidad_total');

        $period->total_ventas = $totalSale;
        $period->total_cierre_caja = $params['totalCierreCaja'];
        if ($period->save()) {
            $response = [
                'success' => true,
                'message' => 'Periodo cerrado con exito!',
                'period' => $period
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Existen parametros incorrectos',
                'errors' => $period->errors
            ];
        }
        return $response;
    }

    public function actionGetDetailPeriod($idUser, $idPeriod)
    {
        $period = Periodo::findOne($idPeriod);
        if ($period) {
             $user = Usuario::findOne($idUser);
            if ($user) {
                //vetnas totales hasta el momento 
                $totalSaleCash = Venta::find()
                    ->where(['>=', 'fecha', $period->fecha_inicio])
                    ->andWhere([ 'usuario_id' => $user->id, 'tipo_pago' => 'efectivo', 'estado' => 'pagado', 'tipo' => 'local'])
                    ->sum('cantidad_total');

                $totalSaleCard = Venta::find()
                    ->where(['>=', 'fecha', $period->fecha_inicio])
                    ->andWhere(['usuario_id' => $user->id, 'tipo_pago' => 'tarjeta', 'estado' => 'pagado','tipo' => 'local'])
                    ->sum('cantidad_total');

                $totalSaleTransfer = Venta::find()
                    ->where(['>=', 'fecha', $period->fecha_inicio])
                    ->andWhere(['usuario_id' => $user->id, 'tipo_pago' => 'transferencia', 'estado' => 'pagado', 'tipo' => 'local'])
                    ->sum('cantidad_total');

                $totalSaleApp = Venta::find()
                    ->where(['>=', 'fecha', $period->fecha_inicio])
                    ->andWhere(['usuario_id' => $user->id, 'estado' => 'pagado', 'tipo' => 'pedidoApp'])
                    ->sum('cantidad_total');

                $totalSale = Venta::find()
                    ->where(['>=', 'fecha', $period->fecha_inicio])
                    ->andWhere(['usuario_id' => $user->id , 'estado' => 'pagado'])
                    ->sum('cantidad_total');
                $response = [
                    'success' => true,
                    'message' => 'detalle de periodo por usuario',
                    'info' => [
                        'fechaInicio' => $period->fecha_inicio,
                        'cajaInicial' => $period->caja_inicial,
                        'totalSaleCash' => $totalSaleCash ? $totalSaleCash : 0,
                        'totalSaleCard' => $totalSaleCard ? $totalSaleCard : 0,
                        'totalSaleTransfer' => $totalSaleTransfer ? $totalSaleTransfer : 0,
                        'totalSale' => $totalSale ? $totalSale: 0,
                        'totalSaleApp' => $totalSaleApp ? $totalSaleApp : 0
                        ]   
                    ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'No existe usuario',
                    'user' => $idUser
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'No existe periodo',
                'period' => $idPeriod
            ];
        }
        return $response;
    }

  
    public function actionExistsPeriodActiveById( $idUser ){
        $period = Periodo::find()
                            ->where(['estado' => true, 'usuario_id' => $idUser])
                            ->one();
        if($period){
            $response = [
                'success' => true,
                'message' => 'Periodo activo',
                'period' => $period
            ];
        }else {
            $response = [
                'success' => true,
                'message' => 'Periodo activo',
                'period' => []
            ];
        }
        return $response;
    }

    public function actionGetDetailSaleByUser($idUser){
        $period = Periodo::find() -> where(['usuario_id' => $idUser, 'estado' => true])->one();

        if ($period) {
             $user = Usuario::findOne($idUser);
            if ($user) {
                //vetnas totales hasta el momento 
                $sales = Venta::find()
                        ->select(['SUM(detalle_venta.cantidad)', 'producto.nombre', 'sum(producto.precio_venta*detalle_venta.cantidad) as total'])
                        ->where(['>=', 'fecha', $period-> fecha_inicio])
                        ->andWhere(['venta.estado' => 'pagado'])
                        ->innerJoin('detalle_venta', 'detalle_venta.venta_id=venta.id')
                        ->innerJoin('producto', 'producto.id= detalle_venta.producto_id')
                        ->andWhere(['usuario_id' => $idUser])
                        ->groupBy(['producto_id', 'producto.nombre'])
                        ->asArray()
                        ->all();

                $salesDrinks = Venta::find()
                        ->select(['sum(producto.precio_venta*detalle_venta.cantidad) as total'])
                        ->where(['>=', 'fecha', $period-> fecha_inicio])
                        ->andWhere(['venta.estado' => 'pagado', 'producto.tipo' => 'bebida'])
                        ->innerJoin('detalle_venta', 'detalle_venta.venta_id=venta.id')
                        ->innerJoin('producto', 'producto.id= detalle_venta.producto_id')
                        ->andWhere(['usuario_id' => $idUser])
                        ->groupBy(['producto.tipo'])
                        ->asArray()
                        ->one();

                $salesFoods = Venta::find()
                        ->select(['sum(producto.precio_venta*detalle_venta.cantidad) as total'])
                        ->where(['>=', 'fecha', $period-> fecha_inicio])
                        ->andWhere(['venta.estado' => 'pagado', 'producto.tipo' => 'comida'])
                        ->innerJoin('detalle_venta', 'detalle_venta.venta_id=venta.id')
                        ->innerJoin('producto', 'producto.id= detalle_venta.producto_id')
                        ->andWhere(['usuario_id' => $idUser])
                        ->groupBy(['producto.tipo'])
                        ->asArray()
                        ->one();
              
                $response = [
                    'success' => true,
                    'message' => 'detalle de periodo por usuario',
                    'info' => [
                        'fechaInicio' => $period->fecha_inicio,
                        'sales' => $sales,
                        'salesDrinks' => $salesDrinks,
                        'salesFoods' =>  $salesFoods
                        ]   
                    ];
            } else {
                $response = [
                    'success' => false,
                    'message' => 'No existe usuario',
                    'user' => $idUser
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'No existe periodo iniciado',
            ];
        }
        return $response;
    }
}
