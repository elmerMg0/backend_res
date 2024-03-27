<?php

namespace app\controllers;

use app\models\ArqueoInventario;
use app\models\DetalleArqueoInventario;
use app\models\Producto;
use DateTime;
use Exception;
use Yii;
use yii\data\Pagination;

class ArqueoInventarioController extends \yii\web\Controller
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
                    'roles' => ['administrador'] // control por roles  permisos
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
    public function actionIndex($name, $pageSize=5)
    {
        if($name === 'undefined')$name = null;
        $query = ArqueoInventario::find()
                    ->select(['arqueo_inventario.fecha', 'usuario.nombres', 'arqueo_inventario.id'])
                    ->innerJoin('usuario', 'arqueo_inventario.usuario_id = usuario.id')
                    ->andFilterWhere(['LIKE', 'UPPER(nombre)',  strtoupper($name)])
                    ->asArray();

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count(),
        ]);

        $data = $query

            ->orderBy('arqueo_inventario.id DESC')
            ->offset($pagination->offset)
            ->limit($pagination->limit)
            ->all();

        $currentPage = $pagination->getPage() + 1;
        $totalPages = $pagination->getPageCount();
        $response = [
            'success' => true,
            'message' => 'lista de arqueo de inventarios',
            'pageInfo' => [
                'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                'previus' => $currentPage == 1 ? null : $currentPage - 1,
                'count' => count($data),
                'page' => $currentPage,
                'start' => $pagination->getOffset(),
                'totalPages' => $totalPages,
            ],
            'data' => $data
        ];
        return $response;
    }

    public function actionCreate(){
        $params = Yii::$app->getRequest()->getBodyParams(); 
        extract($params);
        if($recordList){
            $db = Yii::$app->db;
             $transaction = $db->beginTransaction();
            try{
                $inventaryAudit = new ArqueoInventario();
                $inventaryAudit -> usuario_id = $usuario_id;
                date_default_timezone_set('America/La_Paz');
                $inventaryAudit -> fecha = date('Y-m-d H:i:s');
    
                if($inventaryAudit -> save()){
                    //Guardar el detalle de la auditoria
                    for($i = 0 ; $i < count($recordList); $i ++){
                        $inventaryAuditDetail = new DetalleArqueoInventario();
                        $inventaryAuditDetail -> arqueo_inventario_id = $inventaryAudit -> id;
                        $inventaryAuditDetail -> producto_id = $recordList[$i]['id'];
                        $inventaryAuditDetail -> declaracion = $recordList[$i]['declaration'];
                        $inventaryAuditDetail -> stock = $recordList[$i]['stock'];
                        $inventaryAuditDetail -> nota = $recordList[$i]['note'] ?? '';
                        
                        $product = Producto ::findOne($recordList[$i]['id']);
                        $product -> stock = $recordList[$i]['declaration'];
                        if(!$product -> save()){
                            return $product -> errors;
                            throw new Exception( $product -> errors['error']);
                        }

                        if(!$inventaryAuditDetail -> save()){
                            return $inventaryAuditDetail -> errors;
                            throw new Exception( $inventaryAuditDetail -> errors['error']);
                        }
                    }
                }else{
                    throw new \Exception('No se pudo registrar el inventario');
                }
                $transaction -> commit();
                $response = [
                    'success' => true,
                    'message' => 'Inventario registrado',
                ];


            }catch(Exception $e){
                $transaction->rollBack();
                Yii::$app->getResponse()->setStatusCode(500, 'Data Validation Failed.');
                $response = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
          

        }else{
            $response = [
                'success' => false,
                'message' => 'No existen registros',
            ];
        }
        return $response;
    }

    public function actionDetails($id){
       /*  $inventaryAudit = ArqueoInventario::find()
                         ->where(['arqueo_inventario.id' => $id])
                           
                        ->with(['detalleArqueoInventarios' => function ($query) {
                            $query
                            ->select(['producto.nombre', 'detalle_arqueo_inventario.*'])
                            ->innerjoin('producto', 'detalle_arqueo_inventario.producto_id = producto.id')
                            ->asArray();
                          }])
                         ->asArray()
                         ->all(); */


        $inventaryDetails = DetalleArqueoInventario::find()
                            ->select(['producto.nombre', 'detalle_arqueo_inventario.*'])
                            ->innerjoin('producto', 'detalle_arqueo_inventario.producto_id = producto.id')
                            ->where(['arqueo_inventario_id' => $id])
                            ->asArray()
                            ->all();
        $response = [
            'success' => true,
            'message' => 'Detalle inventario',
            'data' => [
                'inventaryDetails' => $inventaryDetails
            ]
        ];

        return $response;
    }


    public function actionInventaryAudits($pageSize = 7)
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $user = assert($params['usuarioId'])? $params['usuarioId'] : null;
        $fechaIni = assert($params['fechaInicio']) ? $params['fechaInicio'] :  null;
        $fechaFinWhole =  assert($params['fechaFin']) ? $params['fechaFin'] . ' 23:59:58.0000' : null;
        $query = ArqueoInventario::find()
            ->select(['arqueo_inventario.fecha', 'usuario.nombres', 'arqueo_inventario.id'])
            ->innerJoin('usuario','usuario.id = arqueo_inventario.usuario_id')
            ->andFilterWhere(['between', 'fecha', $fechaIni, $fechaFinWhole])
            ->andFilterWhere(['usuario_id' => $user])
            ->orderBy(['arqueo_inventario.id' => SORT_DESC])
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
                'message' => 'lista de arqueo de invetarios ',
                'pageInfo' => [
                    'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                    'previus' => $currentPage == 1 ? null : $currentPage - 1,
                    'count' => count($inventaryAudits),
                    'page' => $currentPage,
                    'start' => $pagination->getOffset(),
                    'totalPages' => $totalPages,
                ],
                'inventaryAudits' => $inventaryAudits
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
}
