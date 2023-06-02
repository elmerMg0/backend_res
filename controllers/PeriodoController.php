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
                'login' => ['POST'],
                'create' => ['POST'],
                'update' => ['POST'],
                'start-period' => ['POST'],
                'close-period' => ['POST']

            ]
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
        //validar que no exista un periodo 
        $periodActive = Periodo::find()
                                ->where(['estado' => true])
                                ->one();
        if (!$periodActive) {
          //  if (!$lastRecord->estado) {
                $period = new Periodo();
                /* $period->fecha_inicio = Date('H-m-d H:i:s'); */
                date_default_timezone_set('America/La_Paz');
                $period->fecha_inicio = date('Y-m-d H:i:s');
                $period->estado = true;
                $period->caja_inicial = $params['cajaInicial'];
                $period->usuario_id = $userId;
                if ($period->save()) {
                    $response = [
                        'success' => true,
                        'message' => 'Periodo iniciado con exito!',
                        'period' => $period
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Existen parametros incorrectos',
                        'errors' => $period->errors
                    ];
                }
          /*   } else {
                $response = [
                    'success' => false,
                    'message' => 'Existe ya un periodo activo',
                    'periodActive' => $lastRecord
                ];
            } */
        } else {
            $response = [
                'success' => false,
                'message' => 'Existe un perido activo!',
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
                        'totalSaleCash' => $totalSaleCash,
                        'totalSaleCard' => $totalSaleCard,
                        'totalSaleTransfer' => $totalSaleTransfer,
                        'totalSale' => $totalSale,
                        'totalSaleApp' => $totalSaleApp
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
    public function actionTest () {
        $period = Periodo::findOne(2);
       /*  $ventas = Venta::find()
                 ->where(['id' => 1000])            
                    ->all(); */
                    $totalSaleCash = Venta::find()
                    /* ->where(['>=', 'fecha', $period->fecha_inicio]) */
                    ->andWhere([ 'usuario_id' => 1, 'tipo_pago' => 'efectivo'])
                    ->all();
        return $totalSaleCash;
    }
}
