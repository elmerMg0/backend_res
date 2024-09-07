<?php

namespace app\controllers;

use app\models\CategoriaGasto;
use app\models\Gasto;
use app\models\UnidadMedida;
use Exception;
use Yii;
use yii\data\Pagination;

class GastoController extends \yii\web\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors["verbs"] = [
            "class" => \yii\filters\VerbFilter::class,
            "actions" => [
                'index' => ['post'],
                'create-expense' => ['post'],
                'update' => ['put', 'post'],
                'get-expenses' => ['GET'],
            ]
        ];
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['options']
        ];
        $behaviors['access'] = [
            'class' => \yii\filters\AccessControl::class,
            'only' => ['create-expense', 'get-expenses'], // acciones a las que se aplicará el control
            'except' => [''],    // acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['create-expense', 'get-expenses'], // acciones que siguen esta regla
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
    public function actionIndex($pageSize = 7)
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $user = isset($params['usuarioId']) && $params['usuarioId'] ? $params['usuarioId'] : null;
        $fechaIni = isset($params['dateStart']) && $params['dateStart'] ? $params['dateStart'] :  null;
        $fechaFinWhole =  isset($params['dateEnd']) && $params['dateEnd'] ? $params['dateEnd'] . ' 23:59:58.0000' : null;
        $categoriaGastoId =  isset($params['categoriaGastoId']) && $params['categoriaGastoId'] ? $params['categoriaGastoId'] : null;
        $reference =  isset($params['referencia']) && $params['referencia'] ? $params['referencia'] : null;

        $query = Gasto::find()
            ->select(['gasto.*', 'categoria_gasto.nombre as categoria_gasto', 'usuario.username as usuario'])
            ->innerJoin('categoria_gasto', 'categoria_gasto.id = gasto.categoria_gasto_id')
            ->innerJoin('usuario', 'usuario.id = gasto.usuario_id')
            ->filterWhere(['>=', 'create_ts', $fechaIni])
            ->andFilterWhere(['<=', 'create_ts', $fechaFinWhole])
            ->andFilterWhere(['usuario_id' => $user])
            ->andFilterWhere(['categoria_gasto_id' => $categoriaGastoId])
            ->andFilterWhere(['like', 'LOWER(referencia)', $reference])
            ->orderBy(['id' => SORT_DESC])
            ->asArray();

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count()
        ]);

        $expenseRecords = $query
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        if ($expenseRecords) {
            $currentPage = $pagination->getPage() + 1;
            $totalPages = $pagination->getPageCount();
            $response = [
                'success' => true,
                'message' => 'lista de gastos',
                'pageInfo' => [
                    'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                    'previus' => $currentPage == 1 ? null : $currentPage - 1,
                    'count' => count($expenseRecords),
                    'page' => $currentPage,
                    'start' => $pagination->getOffset(),
                    'totalPages' => $totalPages,
                ],
                'records' => $expenseRecords
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'No existen registros',
                'sales' => []
            ];
        }
        return $response;

        $response = [
            'success' => true,
            'message' => 'Lista de gastos',
            'records' => $records
        ];

        return $response;
    }

    public function actionGetMeasurementUnit()
    {
        $measurementUnits = UnidadMedida::find()->all();
        $response = [
            'success' => true,
            'message' => 'Lista de unidades de medida',
            'measurementUnits' =>  $measurementUnits
        ];

        return $response;
    }

    public function actionGetExpenseCategories()
    {
        $expenseCategories = CategoriaGasto::find()->all();

        $response = [
            'success' => true,
            'message' => 'Lista de categorias de gastos',
            'expenseCategories' => $expenseCategories
        ];
        return $response;
    }
    public function actionCreate()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $expense = new Gasto();
        $expense->load($params['expense'], "");
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if ($expense->save()) {
                $expenseRecord = new RegistroGastoController('', '');
                for($i = 0; $i < count($params['details']); $i++){
                    $expenseRecord->create($params['details'][$i], $expense->id);
                }

                $transaction->commit();
                Yii::$app->getResponse()->setStatusCode(201);
                $response = [
                    "success" => true,
                    "message" => "Gasto agreado exitosamente",
                    'expense' => $expense 
                ];
            } else {
                throw new Exception(json_encode($expense->errors));
            }
        } catch (Exception $e) {
            $transaction->rollBack();
            $response = [
                "success" => false,
                "message" => "ocurrio un error",
                'errors' => $e ->getMessage()
            ];
        }
        return $response;
    }
}
