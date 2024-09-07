<?php

namespace app\controllers;

use app\models\ClasificacionGrupo;
use app\models\Gasto;
use app\models\Inventario;
use Yii;
use app\models\Periodo;
use app\models\RegistroGasto;
use app\models\TipoPago;
use app\models\Usuario;
use app\models\Venta;
use app\services\PeriodoService;

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
            'only' => ['get-detail-period', 'start-period', 'close-period'], // acciones a las que se aplicará el control
            'except' => [''],    // acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['get-detail-period', 'start-period', 'close-period', 'get-detail-sale-by-user'], // acciones que siguen esta regla
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

    public function actionStartPeriod($userId)
    {

        /* Validar que no pueda agregar otro periodo si ya existe uno. */
        $exists = Periodo::find()->where(['usuario_id' => $userId, 'estado' => true])->all();
        if (!$exists) {
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
        } else {
            $response = [
                'success' => false,
                'message' => 'Ya existe un perido activo, actualice la pagina por favor.'
            ];
        }
        return $response;
    }

    public function actionClosePeriod($idPeriod, $idUser)
    {

        $params = Yii::$app->getRequest()->getBodyParams();
        $totalCierreCaja = $params['totalCierreCaja'];

        $periodoService = new PeriodoService();
        $response = $periodoService->closePeriod($idPeriod, $idUser, $totalCierreCaja);

        return $response;
    }

    public function actionGetDetailPeriod($idUser, $idPeriod)
    {
        $period = Periodo::findOne($idPeriod);
        if ($period) {
            $user = Usuario::findOne($idUser);
            if ($user) {

                $paymentTypes = TipoPago::find()
                                            ->where(['estado' => true])
                                            ->all();
                $quantityBypaymentTypes = [];
                for($i = 0; $i < count($paymentTypes); $i++){
                    $total = Venta::find()
                                ->where(['>=', 'fecha', $period->fecha_inicio])
                                ->andWhere(['usuario_id' => $user->id, 'tipo_pago_id' => $paymentTypes[$i]->id])
                                ->andWhere(['<>', 'estado', 'cancelado'])
                                ->sum('cantidad_total');
                    $quantityBypaymentTypes [] = [
                        'paymentType' => $paymentTypes[$i]['descripcion'],
                        'totalSale' => $total
                    ];
                }                                            
                $totalSale = Venta::find()
                    ->where(['>=', 'fecha', $period->fecha_inicio])
                    ->andWhere(['usuario_id' => $user->id, 'estado' => 'pagado'])
                    ->sum('cantidad_total');

                $expenses = Gasto::find()
                    ->where(['usuario_id' => $idUser, 'pagado' => true])
                    ->andWhere(['>=', 'fecha', $period->fecha_inicio])
                    ->sum('total');

                /* Obtener si existen ventas no pagadas */
                $existSalesOpen = Venta::find()
                    ->where(['>=', 'fecha', $period->fecha_inicio])
                    ->andWhere(['usuario_id' => $user->id, 'estado' => 'consumiendo'])
                    ->exists();

                $response = [
                    'success' => true,
                    'message' => 'detalle de periodo por usuario',
                    'info' => [
                        'fechaInicio' => $period->fecha_inicio,
                        'cajaInicial' => $period->caja_inicial,
                        'quantityBypaymentTypes' => $quantityBypaymentTypes,
                        'totalExpenses' => $expenses ? $expenses : 0,
                        'existSalesOpen' => $existSalesOpen,
                        'totalSale' => $totalSale
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


    public function actionExistsPeriodActiveById($idUser)
    {
        $period = Periodo::find()
            ->where(['estado' => true, 'usuario_id' => $idUser])
            ->one();
        if ($period) {
            $response = [
                'success' => true,
                'message' => 'Periodo activo',
                'period' => $period
            ];
        } else {
            $response = [
                'success' => true,
                'message' => 'Periodo activo',
                'period' => []
            ];
        }
        return $response;
    }

    public function actionGetDetailSaleByUser($idUser)
    {
        $period = Periodo::find()
            ->where(['usuario_id' => $idUser])
            ->orderBy(['fecha_inicio' => SORT_DESC])
            ->one();

        if ($period) {
            $user = Usuario::findOne($idUser);
            if ($user) {
                //vetnas totales hasta el momento 
                $grupClassication = ClasificacionGrupo::find()
                                                ->where(['estado' => true])
                                                ->all();
                $grupClassicationList = [];
                for($i = 0; $i < count($grupClassication); $i++){
                    $total = Venta::find()
                    ->select(['sum(producto.precio_venta*detalle_venta.cantidad) as total'])
                    ->where(['>=', 'fecha', $period->fecha_inicio])
                    ->andWhere(['venta.estado' => 'pagado','usuario_id' => $idUser, 'clasificacion_grupo.id' => $grupClassication[$i]->id])
                    ->innerJoin('detalle_venta', 'detalle_venta.venta_id=venta.id')
                    ->innerJoin('producto', 'producto.id= detalle_venta.producto_id')
                    ->innerJoin('categoria', 'categoria.id=producto.categoria_id')
                    ->innerJoin('clasificacion_grupo', 'clasificacion_grupo.id=categoria.clasificacion_grupo_id')
                    ->andWhere(['<>', 'detalle_venta.estado', 'cancelado'])
                    ->asArray()
                    ->one();

                    $grupClassicationList[] = [
                        'name' => $grupClassication[$i]->descripcion,
                        'total' => $total['total']
                    ];
                }

                $sales = Venta::find()
                    ->select(['SUM(detalle_venta.cantidad)', 'producto.nombre', 'sum(producto.precio_venta*detalle_venta.cantidad) as total'])
                    ->innerJoin('detalle_venta', 'detalle_venta.venta_id=venta.id')
                    ->innerJoin('producto', 'producto.id= detalle_venta.producto_id')
                    ->where(['>=', 'fecha', $period->fecha_inicio])
                    ->andWhere(['usuario_id' => $idUser])
                    ->andWhere(['venta.estado' => 'pagado'])
                    ->andWhere(['<>', 'detalle_venta.estado', 'cancelado'])
                    ->groupBy(['producto_id', 'producto.nombre'])
                    ->asArray()
                    ->all();

               

                $response = [
                    'success' => true,
                    'message' => 'detalle de periodo por usuario',
                    'info' => [
                        'fechaInicio' => $period->fecha_inicio,
                        'sales' => $sales,
                        'grupClassicationList' => $grupClassicationList,
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
                'message' => 'No se encontro registro',
            ];
        }
        return $response;
    }
}
