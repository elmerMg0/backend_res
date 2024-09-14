<?php

namespace app\controllers;

use app\models\ConceptoMovAlmacen;
use app\models\Insumo;
use app\models\MovimientoAlmacen;
use Exception;
use Yii;
use yii\data\Pagination;

class MovimientoAlmacenController extends \yii\web\Controller
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
        $warehouseId =  isset($params['almacen_id']) ? $params['almacen_id'] : null;
        $conceptId =  isset($params['concepto_mov_almacen_id']) ? $params['concepto_mov_almacen_id'] : null;


        $query = MovimientoAlmacen::find()
            ->select(['movimiento_almacen.fecha', 'usuario.nombres as usuario', 'movimiento_almacen.id', 'almacen.descripcion as almacen', 'concepto_mov_almacen.descripcion as concepto', 'movimiento_almacen.nota', 'movimiento_almacen.almacen_id', 'movimiento_almacen.total'])
            ->innerJoin('usuario', 'usuario.id = movimiento_almacen.usuario_id')
            ->innerJoin('almacen', 'almacen.id = movimiento_almacen.almacen_id')
            ->innerJoin('concepto_mov_almacen', 'concepto_mov_almacen.id = movimiento_almacen.concepto_mov_almacen_id')
            ->filterWhere(['>=', 'fecha', $fechaIni])
            ->andFilterWhere(['<=', 'fecha', $fechaFinWhole])
            ->andFilterWhere(['usuario_id' => $user])
            ->andFilterWhere(['almacen_id' => $warehouseId])
            ->andFilterWhere(['concepto_mov_almacen_id' => $conceptId])
            ->orderBy(['movimiento_almacen.id' => SORT_DESC])
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
            $model = new MovimientoAlmacen();
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
            $warehouseMovementModel = $this->create($warehouseMovement);
            $model = $this->WarehouseMovementFactory($type);
            for ($i = 0; $i < count($movementDetails); $i++) {
                /* Actualizar Inventario o inventarioPres */
                $model->create([
                    ...$movementDetails[$i],
                    'movimiento_almacen_id' => $warehouseMovementModel->id, 'costo_unitario' => $movementDetails[$i]['ultimo_costo'], 'cantidad' => $movementDetails[$i]['cantidad_movimiento']
                ]);

                //validar si es de SALIDA (-) o es de entrada (+)
                $concept = ConceptoMovAlmacen::findOne($warehouseMovement['concepto_mov_almacen_id']);
                $cantidad = 0;

                $cantidad = $concept -> tipo ===  'ENTRADA' ? $movementDetails[$i]['cantidad_movimiento'] : $movementDetails[$i]['cantidad_movimiento'] * -1;

                $inventaryModel = $this->inventaryFactory($type);
                $inventaryModel->update(
                    [...$movementDetails[$i], 'almacen_id' => $warehouseMovement['almacen_id']
                    ,'cantidad' => $cantidad
                    ]
                );
            }

            $transaction->commit();
            $response = [
                'success' => true,
                'message' => 'Movimiento de almacen creado exitosamente',
            ];
        } catch (Exception $e) {
            $transaction->rollback();
            throw new Exception($e->getMessage());
        }
        return $response;
    }

    private function WarehouseMovementFactory($type)
    {
        switch ($type) {
            case 'inventario':
                return new MovimientoAlmacenDetalleController('', '');
            case 'inventario-pres':
                return new MovimientoAlmacenDetallePresController('', '');
            default:
                return null;
        }
    }

    private function inventaryFactory($type)
    {
        switch ($type) {
            case 'inventario':
                return new InventarioController('', '');
            case 'inventario-pres':
                return new InventarioPresController('', '');
            default:
                return null;
        }
    }
}
