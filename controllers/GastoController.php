<?php

namespace app\controllers;

use app\models\CategoriaGasto;
use app\models\Gasto;
use app\models\UnidadMedida;
use Exception;
use Yii;

class GastoController extends \yii\web\Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors["verbs"] = [
            "class" => \yii\filters\VerbFilter::class,
            "actions" => [
                'index' => ['get'],
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
                    'roles' => ['administrador'] // control por roles  permisos
                ],
                [
                    'allow' => true, // permitido o no permitido
                    'actions' => ['create'], // acciones que siguen esta regla
                    'roles' => ['mesero'] // control por roles  permisos
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

    public function actionGetMeasurementUnit(){
        $measurementUnits = UnidadMedida::find()->all();
        $response = [
            'success' => true,
            'message' => 'Lista de unidades de medida',
            'measurementUnits' =>  $measurementUnits
        ];

        return $response;
    }

    public function actionGetExpenseCategories(){
        $expenseCategories = CategoriaGasto::find()->all();

        $response = [
            'success' => true,
            'message' => 'Lista de categorias de gastos',
            'expenseCategories' => $expenseCategories
        ];
        return $response;
    }
    public function actionCreateExpense(){
        $params = Yii::$app->getRequest()->getBodyParams();
        $expense = new Gasto();
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

    public function actionGetExpenses(){
        $expenses = Gasto::find() -> all();
        $reponse = [
            'success' => true,
            'message' => 'Lista de gastos',
            'expenses' => $expenses 
        ];
        return $reponse;
    }
}
