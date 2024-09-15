<?php

namespace app\controllers;

use app\models\TraspasoAlmacen;
use Exception;
use Yii;
use yii\data\Pagination;

class TraspasoAlmacenController extends \yii\web\Controller
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
            ]
        ];
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['options']
        ];
        $behaviors['access'] = [
            'class' => \yii\filters\AccessControl::class,
            'only' => ['create', 'index'], // acciones a las que se aplicará el control
            'except' => [''],    // acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['index', 'create'], // acciones que siguen esta regla
                    'roles' => ['administrador', 'configurador', 'almacenista'] // control por roles  permisos
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

    public function actionIndex($pageSize = 5)
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $user = isset($params['usuarioId']) ? $params['usuarioId'] : null;
        $fechaIni = isset($params['fechaInicio']) ? $params['fechaInicio'] :  null;
        $fechaFinWhole =  isset($params['fechaFin']) ? $params['fechaFin'] . ' 23:59:58.0000' : null;
        $warehouseOriginId =  isset($params['almacen_origen_id']) ? $params['almacen_origen_id'] : null;
        $warehouseDestId =  isset($params['almacen_destino_id']) ? $params['almacen_destino_id'] : null;


        $query = TraspasoAlmacen::find()
            ->select(['traspaso_almacen.fecha', 'usuario.nombres as usuario', 'traspaso_almacen.id', 'traspaso_almacen.nota', 'almacen_origen.descripcion as almacen_origen', 'almacen_destino.descripcion as almacen_destino', 'almacen_origen_id'])
            ->innerJoin('usuario', 'usuario.id = traspaso_almacen.usuario_id')
            ->innerJoin('almacen almacen_origen', 'almacen_origen.id = traspaso_almacen.almacen_origen_id')
            ->innerJoin('almacen almacen_destino', 'almacen_destino.id = traspaso_almacen.almacen_destino_id')
            ->filterWhere(['>=', 'fecha', $fechaIni])
            ->andFilterWhere(['<=', 'fecha', $fechaFinWhole])
            ->andFilterWhere(['usuario_id' => $user])
            ->andFilterWhere(['almacen_origen_id' => $warehouseOriginId])
            ->andFilterWhere(['almacen_destino_id' => $warehouseDestId])
            ->orderBy(['traspaso_almacen.id' => SORT_DESC])
            ->asArray();

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count()
        ]);

        $inventaryAudits = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        if ($inventaryAudits) {
            $currentPage = $pagination->getPage() + 1;
            $totalPages = $pagination->getPageCount();
            $response = [
                'success' => true,
                'message' => 'lista de movimientos de inventarios ',
                'pageInfo' => [
                    'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                    'previus' => $currentPage == 1 ? null : $currentPage - 1,
                    'count' => count($inventaryAudits),
                    'page' => $currentPage,
                    'start' => $pagination->getOffset(),
                    'totalPages' => $totalPages,
                ],
                'records' => $inventaryAudits
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'No existen inventarios',
                'sales' => []
            ];
        }
        return $response;
    }

    public function create($params)
    {
        try {
            $model = new TraspasoAlmacen();
            $model->load($params, '');
            $model->save();
            if (!$model->save()) {
                throw new Exception(json_encode($model->errors));
            }
            $response = $model;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $response;
    }

    public function actionCreate()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        extract($params);

        $transaction = Yii::$app->db->beginTransaction();
        try {
            $model = $this->create($warehouseTransfer);

            $modelFactory = $this->transferMovementFactory($warehouseTransfer['origin']);

            // 11 salida por traspaso de almacen, 9 entrada por traspaso de almacen
            $warehouseMovement = new MovimientoAlmacenController('', '');

            $warehouseMovementEntry = $warehouseMovement->create([
                'almacen_id' => $warehouseTransfer['almacen_destino_id'],
                'usuario_id' => $warehouseTransfer['usuario_id'],
                'concepto_mov_almacen_id' => 9,
            ]);

            $warehouseMovementExit = $warehouseMovement->create([
                'almacen_id' => $warehouseTransfer['almacen_origen_id'],
                'usuario_id' => $warehouseTransfer['usuario_id'],
                'concepto_mov_almacen_id' => 17,
            ]);


            for ($i = 0; $i < count($transferDetails); $i++) {
                $modelFactory->create([
                    ...$transferDetails[$i],
                    'traspaso_almacen_id' => $model->id, 'costo_unitario' => $transferDetails[$i]['ultimo_costo'],
                    'cantidad' => $transferDetails[$i]['cantidad_traspaso'],
                    'existencia' => $transferDetails[$i]['cantidad']
                ]);

                /* 
                    1. Crear movimendo de almacen salida por traspaso de almacen = 17
                    2. dependiendo del tipo de origin, crear movimiento de almacen insumo o presentacion.
                    3. actualizar el inventario de insumo o presentacion
                    4. Crear movimendo de almacen entrada por traspaso de almacen = 9
                    5. dependiendo del tipo de destination, crear movimiento de almacen insumo o presentacion.
                    6. actualizar el inventario de insumo o presentacion 
                */

                /* Crear detalle de movimiento de almacen salida*/
                $exitMovementDetail = $this->wareshouseMovementFactory($warehouseTransfer['origin']);
                $exitMovementDetail->create([
                    ...$transferDetails[$i],
                    'cantidad' => $transferDetails[$i]['cantidad_traspaso'],
                    'movimiento_almacen_id' => $warehouseMovementExit->id,
                    'costo_unitario' => $transferDetails[$i]['ultimo_costo']
                ]);



                $rendimiento = 1;
                if ($warehouseTransfer['origin'] === 'Presentaciones' && $warehouseTransfer['destination'] === 'Centro de consumo') {
                    $rendimiento = $transferDetails[$i]['rendimiento'];
                }


                //Crear detalle de movimiento de almacen entrada 
                $entryMovementDetail = $this->wareshouseMovementFactory($warehouseTransfer['destination']);
                $entryMovementDetail->create([
                    ...$transferDetails[$i],
                    'cantidad' => $transferDetails[$i]['cantidad_traspaso'] * $rendimiento,
                    'movimiento_almacen_id' => $warehouseMovementEntry->id,
                    'costo_unitario' => $transferDetails[$i]['ultimo_costo'] / $rendimiento
                ]);


                //Actualizar inventario salida
                $inventaryModel = $this->inventaryFactory($warehouseTransfer['origin']);
                $inventaryModel->update(
                    [
                        ...$transferDetails[$i], 'cantidad' => -$transferDetails[$i]['cantidad_traspaso']
                    ]
                );

                //Actualizar inventario entrada
                $inventaryModel = $this->inventaryFactory($warehouseTransfer['destination']);
                $inventaryModel->update(
                    [
                        ...$transferDetails[$i],
                        'cantidad' => $transferDetails[$i]['cantidad_traspaso'] * $rendimiento,
                        'almacen_id' => $warehouseTransfer['almacen_destino_id']
                    ]
                );

                //Actualizar inventario entrada si el item no maneja existencia
                if(!$transferDetails[$i]['inventariable'] && $warehouseTransfer['destination'] === 'Centro de consumo'){
                    $warehouseMovementExit = $warehouseMovement->create([
                        'almacen_id' => $warehouseTransfer['almacen_destino_id'],
                        'usuario_id' => $warehouseTransfer['usuario_id'],
                        'concepto_mov_almacen_id' => 17,
                    ]);
        
                    $entryMovementDetail->create([
                        ...$transferDetails[$i],
                        'cantidad' => $transferDetails[$i]['cantidad_traspaso'] * $rendimiento,
                        'movimiento_almacen_id' => $warehouseMovementExit->id,
                        'costo_unitario' => $transferDetails[$i]['ultimo_costo'] / $rendimiento
                    ]);
                    $inventaryModel->update(
                        [
                            ...$transferDetails[$i], 
                            'cantidad' => -$transferDetails[$i]['cantidad_traspaso'] * $rendimiento,
                            'almacen_id' => $warehouseTransfer['almacen_destino_id']
                        ]
                    );
              
                    $presentationController = new InsumoController('', '');
                    $presentationController->validateStockMin($transferDetails[$i]['insumo_id']);
                }
            }

            $transaction->commit();
            $response = [
                'success' => true,
                'message' => 'Traspaso de almacen creado exitosamente',
            ];
        } catch (Exception $e) {
            $transaction->rollback();
            throw new Exception($e->getMessage());
        }
        return $response;
    }

    private function transferMovementFactory($type)
    {
        switch ($type) {
            case 'Centro de consumo':
                return new TraspasoAlmacenInsumoController('', '');
            case 'Presentaciones':
                return new TraspasoAlmacenPresentacionController('', '');
            default:
                return null;
        }
    }
    private function wareshouseMovementFactory($type)
    {
        switch ($type) {
            case 'Centro de consumo':
                return new MovimientoAlmacenDetalleController('', '');
            case 'Presentaciones':
                return new MovimientoAlmacenDetallePresController('', '');
            default:
                return null;
        }
    }
    private function inventaryFactory($type)
    {
        switch ($type) {
            case 'Centro de consumo':
                return new InventarioController('', '');
            case 'Presentaciones':
                return new InventarioPresController('', '');
            default:
                return null;
        }
    }
}
