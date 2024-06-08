<?php

namespace app\controllers;

use app\models\Gasto;
use app\models\Inventario;
use app\models\Periodo;
use app\models\Producto;
use app\models\RegistroGasto;
use Exception;
use Yii;
use yii\data\Pagination;

class RegistroGastoController extends \yii\web\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors["verbs"] = [
            "class" => \yii\filters\VerbFilter::class,
            "actions" => [
                'index' => ['get'],
                'create-record' => ['post'],
                'get-expense-records-filtered' => ['post'],
                'update-expense-record' => ['get']
            ]
        ];
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['options']
        ];
        $behaviors['access'] = [
            'class' => \yii\filters\AccessControl::class,
            'only' => ['create-record', 'get-expense-records-filtered', 'update-expense-record', 'expenses-period'], // acciones a las que se aplicará el control
            'except' => [''],    // acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['create-record', 'get-expense-records-filtered', 'update-expense-record', 'expenses-period'], // acciones que siguen esta regla
                    'roles' => ['administrador'] // control por roles  permisos
                ],
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['create-record', 'expenses-period', 'update-expense-record'], // acciones que siguen esta regla
                    'roles' => ['mesero', 'cajero'] // control por roles  permisos
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

    public function actionCreateRecord(){
        $params = Yii::$app->getRequest()->getBodyParams();
        $expense = new RegistroGasto();
        $expense -> load($params,"");
        date_default_timezone_set('America/La_Paz');
        $expense -> fecha = date('Y-m-d H:i:s');
        try{
            $trasaction = Yii::$app->db->beginTransaction();
            if($expense->save()){   
                /* Se incrementa stock segun la cantidad enviada  */
                $expenseRecord = Gasto::findOne($expense->gasto_id);
                $product = Producto::find() -> where(['nombre' => $expenseRecord -> nombre]) -> one();
                if($product && $product -> stock_active === true){
                    /* Creamos registro de inventario */
                    $inventaryNew = new Inventario();
                    date_default_timezone_set('America/La_Paz');
                    $inventaryNew -> fecha = date('Y-m-d H:i:s');
                    $inventaryNew -> producto_id = $product -> id;
                    $inventaryNew -> stock = $product -> stock;
                    $inventaryNew -> nuevo_stock = $expense -> cantidad;
                    $inventaryNew -> total = $product -> stock + $expense -> cantidad;
                    $inventaryNew -> last_one = true ;
                    //validamos que relamente admita stock
                    if($product -> stock_active === true)$product -> stock = $product -> stock + $expense -> cantidad;

                    if(!$inventaryNew -> save() || !$product -> save()){
                        throw new Exception('No se pudo registrar el gasto');
                    }

                    $inventaryOld = Inventario::find()
                        ->where(['producto_id' => $product -> id, 'last_one' => true])
                        ->one();
                        
                    if($inventaryOld){
                        $inventaryOld -> last_one = false;
                        if(!$inventaryOld -> save()){
                            throw new Exception('No se pudo registrar el gasto');
                        }
                    }
                }
                $trasaction->commit();
                Yii::$app->getResponse()->setStatusCode(201);
                $response = [
                    "success" => true,
                    "message" => "Registro gasto agreado exitosamente",
                    'expense' => $expense
                ];
            }else{
                //Cuando hay error en los tipos de datos ingresados 
                throw new Exception('No se pudo registrar el gasto');
                Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed');
                $response = [
                    "success" => false,
                    "message" => "Existen parametros incorrectos",
                    'errors' => $expense->errors
                ];
            }
        }catch(Exception $e){
            $trasaction->rollBack();
            Yii::$app -> getResponse() -> setStatusCode(500);
            $response = [
                "success" => false,
                "message" => "ocurrio un error",
                'errors' => $e
            ];
        }
        return $response;
    }

    public function actionGetExpenseRecordsFiltered($pageSize = 5){
        $params = Yii::$app -> getRequest() -> getBodyParams();

        $dateStart = assert($params['dateStart']) ? $params['dateStart'] :  null;
        $dateEnd = assert($params['dateEnd']) ? $params['dateEnd'] : null;
        $usuarioId = assert($params['usuarioId']) ? $params['usuarioId'] : null;
        if($dateEnd) $dateEnd = $dateEnd . ' ' . '23:59:00.000';
        $name = assert($params['name']) ? $params['name'] : null;


        $query = RegistroGasto::find()
                    ->select(['registro_gasto.*', 'gasto.nombre as nombre', 'usuario.nombres'])
                    ->innerjoin('gasto', 'gasto.id = registro_gasto.gasto_id')
                    ->innerjoin('usuario', 'usuario.id = registro_gasto.usuario_id')
                    ->filterWhere(['like', 'LOWER(gasto.nombre)', $name])
                    ->andFilterWhere(['>=', 'registro_gasto.fecha', $dateStart])
                    ->andFilterWhere(['<=', 'registro_gasto.fecha', $dateEnd])
                    ->andFilterWhere(['usuario_id' => $usuarioId]);
        //->select(['usuario.id', 'usuario.nombres', 'usuario.tipo', 'usuario.url_image', 'usuario.estado', 'usuario.username'])
//   ->andFilterWhere(['LIKE', 'UPPER(nombres)',  strtoupper($name)]);

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count(),
        ]);

        $expensesRecords = $query
            ->orderBy('id DESC')
            ->offset($pagination->offset)
            ->limit($pagination->limit)        
            ->asArray()
            ->all();

        $currentPage = $pagination->getPage() + 1;
        $totalPages = $pagination->getPageCount();
        $response = [
            'success' => true,
            'message' => 'lista de gastos',
            'pageInfo' => [
                'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                'previus' => $currentPage == 1 ? null: $currentPage - 1,
                'count' => count($expensesRecords),
                'page' => $currentPage,
                'start' => $pagination->getOffset(),
                'totalPages' => $totalPages,
            ],
            'expenses' => $expensesRecords
        ];
        return $response;
    }

    public function actionUpdateExpenseRecord ($idExpenseRecord){
        $expenseRecord = RegistroGasto::findOne($idExpenseRecord);

        if($expenseRecord){
            $expenseRecord -> estado = 'pagado';
            if($expenseRecord -> save()){
                $response = [
                    'success' => true, 
                    'message' => 'Registro de gasto actualizado'
                ];
            }else{
                Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed');
                $response = [
                    "success" => false,
                    "message" => "Existen parametros incorrectos",
                    'errors' => $expenseRecord->errors
                ];
            }
        }else{
            Yii::$app->getResponse()->setStatusCode(500);
            $response = [
                "success" => false,
                "message" => "No se encontro el registro",
            ];
        }
        return $response;
    }


    public function actionExpensesPeriod($pageSize = 5){
        $params = Yii::$app->getRequest()->getBodyParams();
        $period = Periodo::findOne($params['idPeriod']);

        $query = RegistroGasto::find()
                ->select(['registro_gasto.*', 'gasto.nombre as nombre', 'usuario.nombres'])
                ->innerjoin('usuario', 'usuario.id = registro_gasto.usuario_id')
                ->innerjoin('gasto', 'gasto.id = registro_gasto.gasto_id')
                ->where(['usuario_id' => $params['idUser']])
                ->andWhere(['>=', 'fecha', $period->fecha_inicio])
                ->orderBy(['registro_gasto.id' => SORT_DESC])
                ->asArray();

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count()
        ]);

        $expenses = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        if ($expenses) {
            $currentPage = $pagination->getPage() + 1;
            $totalPages = $pagination->getPageCount();
            $response = [
                'success' => true,
                'message' => 'lista de gastos',
                'pageInfo' => [
                    'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                    'previus' => $currentPage == 1 ? null : $currentPage - 1,
                    'count' => count($expenses),
                    'page' => $currentPage,
                    'start' => $pagination->getOffset(),
                    'totalPages' => $totalPages,
                ],
                'expenses' => $expenses
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'Lista de gastos por dia',
                'expenses' => []
            ];
        }
        return $response;
    }
}
