<?php

namespace app\controllers;

use app\models\Mesa;
use app\models\Salon;
use Yii;
class MesaController extends \yii\web\Controller
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
                'get-category' => ['get'],

            ]
        ];
        $behaviors['authenticator'] = [         	
            'class' => \yii\filters\auth\HttpBearerAuth::class,         	
            'except' => ['options']     	
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
/* Reserva(table_id) */
    public function actionGetTables ($idLounge){
        $lounge = Salon::findOne($idLounge);
        $tables = Mesa::find()
                        ->where(['salon_id' => $idLounge])
                        ->orderBy(['id' => SORT_ASC])
                        ->all();
        if($tables){
                $response = [
                'success' => true,
                'message' => 'Lista de mesas',
                'tables' => $tables,
                'lounge' => $lounge
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'NO existen mesas',
                'tables' => []
            ];
        }
        return $response;
    }
    public function actionUpdate ($idTable) {
        $table = Mesa::findOne($idTable);
        if($table) {
            $params = Yii::$app -> getRequest() -> getBodyParams();
            $table -> load($params, '');
            if($table -> save()){
                $response = [
                    'success' => true,
                    'message' => 'Mesa actulizada exitosamente.',
                    'tables' => $table,
                ];
            }else{
                $response = [
                    'success' => false,
                    'message' => 'Existe erorres en los parametros',
                    'table' => []
                ];
            }
        }else{

        }
        return $response;
    }

}
