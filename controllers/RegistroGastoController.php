<?php

namespace app\controllers;

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
                'create' => ['post'],
                'update' => ['put', 'post'],
                'delete' => ['delete'],
            ]
        ];
        $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['options']
        ];
        $behaviors['access'] = [
            'class' => \yii\filters\AccessControl::class,
            'only' => ['create'], // acciones a las que se aplicará el control
            'except' => [''],    // acciones a las que no se aplicará el control
            'rules' => [
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['create'], // acciones que siguen esta regla
                    'roles' => ['administrador'] // control por roles  permisos
                ],
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['create'], // acciones que siguen esta regla
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
        return $this->render('index');
    }

    public function actionCreateRecord(){
        $params = Yii::$app->getRequest()->getBodyParams();
        $expense = new RegistroGasto();
        $expense -> load($params,"");
        //date_default_timezone_set('America/La_Paz');
        //$expense -> create_ts = Date("Y-m-d H:i:s");
        try{
            if($expense->save()){   
                //todo ok
                Yii::$app->getResponse()->setStatusCode(201);
                $response = [
                    "success" => true,
                    "message" => "Gasto agreado exitosamente",
                    'expense' => $expense
                ];
            }else{
                //Cuando hay error en los tipos de datos ingresados 
                Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed');
                $response = [
                    "success" => false,
                    "message" => "Existen parametros incorrectos",
                    'errors' => $expense->errors
                ];
            }
        }catch(Exception $e){
        //cuando no se definen bien las reglas en el modelo ocurre este error, por ejemplo required no esta en modelo y en la base de datos si, 
        //existe incosistencia
            $response = [
                "success" => false,
                "message" => "ocurrio un error",
                'errors' => $e
            ];
        }
        return $response;
    }

    public function actionGetExpenseRecords($pageSize = 5){
       // if($name === 'undefined')$name = null;
        $query = RegistroGasto::find()
                                ->select(['registro_gasto.*', 'gasto.nombre as nombre'])
                                ->innerjoin('gasto', 'gasto.id = registro_gasto.gasto_id');
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
        'message' => 'lista de clientes',
        'pageInfo' => [
            'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
            'previus' => $currentPage == 1 ? null: $currentPage - 1,
            'count' => count($expensesRecords),
            'page' => $currentPage,
            'start' => $pagination->getOffset(),
            'totalPages' => $totalPages,
        ],
        'expensesRecords' => $expensesRecords
        ];
        return $response;
    }

}
