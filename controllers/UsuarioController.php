<?php

namespace app\controllers;

use app\models\Usuario;
use GuzzleHttp\Psr7\Response;
use PhpParser\Node\Stmt\Catch_;
use Yii;
use Exception;
use PhpParser\Node\Stmt\Break_;
use yii\data\Pagination;
use yii\helpers\Json;
use yii\web\UploadedFile;

class UsuarioController extends \yii\web\Controller
{
    public function behaviors(){
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'login' => [ 'POST' ],
                'create-user'=>['POST'],
                'edit-user'=>['POST']

            ]
         ];
         $behaviors['authenticator'] = [
            'class' => \yii\filters\auth\HttpBearerAuth::class,
            'except' => ['options']
        ];
        return $behaviors;
    }

    public function beforeAction( $action ) {
        if (Yii::$app->getRequest()->getMethod() === 'OPTIONS') {         	
            Yii::$app->getResponse()->getHeaders()->set('Allow', 'POST GET PUT');         	
            Yii::$app->end();     	
        }     
        $this->enableCsrfValidation = false;
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        return parent::beforeAction($action);
    }

   
    public function actionCreateUser(){
        //$params = Yii::$app->getRequest()->getBodyParams();
        $user = new Usuario();
        $file = UploadedFile::getInstanceByName('file');
        $data = Json::decode(Yii::$app->request->post('data'));

        // $data = Json::decode(Yii::$app->request->post('data'));
        if($file){
            $fileName = uniqid() . '.' . $file->getExtension();
            $file->saveAs(Yii::getAlias('@app/web/upload/') . $fileName);
            $user->url_image = $fileName;
        }
        try{
  
            $user->nombres = $data["nombres"];
            $user->username = $data["username"];
            $user->password_hash = Yii::$app->getSecurity()->generatePasswordHash($data["password"]);
            $user->access_token = Yii::$app->security->generateRandomString();
            $user->tipo = $data["tipo"];
            $user->estado = $data["estado"];

            if($user->save()){
                $auth = Yii::$app->authManager;
                $role = $auth->getRole($data['tipo']);
                $auth -> assign($role, $user -> id);
                Yii::$app->getResponse()->getStatusCode(201);
                $response = [
                    'success'=> true,
                    'message'=> 'registered user',
                    'usuario'=>$user
                ];
                
            }else{
                Yii::$app->getResponse()->setStatusCode(422,'Data Validation Failed.');
                $response = [
                    'success' => false,
                    'message' => 'Wrong parameters',
                    'usuario' => $user->errors,
                ];
            }
        } catch(Exception $e){
            Yii::$app->getResponse()->getStatusCode();
            $response = [
                'success'=>false,
                'message'=> 'Error Registering User',
                'errors'=> $e->getMessage()
            ];
        }
        
        return $response;
    }
   /*  public function actionDeleteUser($id){
        $params= Usuario::findOne($id);
        if($params){
            try{
                $url_image = $params->url_image;
                $params->delete();
                $pathFile = Yii::getAlias('@webroot/upload/'.$url_image);
                unlink($pathFile);
                $response = [
                    'success'=>true,
                    'message'=>'User deleted'
                ];
            }catch(Exception $e){
                Yii::$app->getResponse()->getStatusCode(409);
                $response = [
                    'success'=> false,
                    'message'=>'Elimination failed',
                    'code'=>$e->getCode()
                ];
            }catch(Exception $e){
                Yii::$app->getResponse()->setStatusCode(422,'Data validation failed');
                $response = [
                    'success' => false,
                    'message'=>$e->getMessage(),
                    'code' => $e->getCode()
                ];
        }
        }else{
            Yii::$app->getResponse()->getStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'user not found',
                
            ];
        }
        return $response;
    } */
    public function actionEditUser($id){
        $user = Usuario::findOne($id);
        if ($user) {
            $data = JSON::decode(Yii::$app->request->post('data'));
            $user->load($data, '');
            
           /*  if(isset($data["password"])){
                $user->password_hash = Yii::$app->getSecurity()->generatePasswordHash($data["password"]);
            }            
            $user->access_token = Yii::$app->security->generateRandomString(); */
            
            $image = UploadedFile::getInstanceByName('file');
            if ($image) {
                $url_image = $user->url_image;
                $imageOld = Yii::getAlias('@app/web/upload/' . $url_image);
                if(file_exists($imageOld) && $url_image){
                    unlink($imageOld);
                    /* Eliminar */
                }
                $fileName = uniqid().'.'.$image->getExtension();
                $image->saveAs(Yii::getAlias('@app/web/upload/') . $fileName);
                $imageNew = Yii::getAlias('@app/web/upload/' . $fileName);
                if(file_exists($imageNew)){
                    $user -> url_image = $fileName;
                }else{
                    return $response = [
                        'success' => false,
                        'message' => 'Ocurrio un error!',
                    ];
                }
            }
            try {

                if ($user->save()) {

                    $response = [
                        'success' => true,
                        'message' => 'Usuario Actualizado',
                        'user' => $user
                    ];
                } else {
                    Yii::$app->getResponse()->setStatusCode(422, 'Data Validation Failed');
                    $response = [
                        'success' => false,
                        'message' => 'Existe errores en los campos',
                        'error' => $user->errors
                    ];
                }
            } catch (Exception $e) {
                Yii::$app->getResponse()->setStatusCode(500);
                $response = [
                    'success' => false,
                    'message' => 'Error de codigo',
                    'error' => $e->getMessage()
                ];
            }
        } else {
            Yii::$app->getResponse()->setStatusCode(404);
            $response = [
                'success' => false,
                'message' => 'Usuario no encontrado',
            ];
        }
        return $response;
    }
    public function actionIndex($pageSize = 5){
        $query = Usuario::find()
        ->select(['usuario.id', 'usuario.nombres', 'usuario.tipo', 'usuario.url_image', 'usuario.estado', 'usuario.username']);

        $pagination = new Pagination([
            'defaultPageSize' => $pageSize,
            'totalCount' => $query->count(),
        ]);

        $users = $query
                        ->orderBy('id DESC')
                        ->offset($pagination->offset)
                        ->limit($pagination->limit)        
                        ->all();
        
        $currentPage = $pagination->getPage() + 1;
        $totalPages = $pagination->getPageCount();
        $response = [
        'success' => true,
        'message' => 'lista de clientes',
        'pageInfo' => [
            'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
            'previus' => $currentPage == 1 ? null: $currentPage - 1,
            'count' => count($users),
            'page' => $currentPage,
            'start' => $pagination->getOffset(),
            'totalPages' => $totalPages,
            'users' => $users
            ]
        ];
        return $response;
    }
    public function actionGetAllUsers(){
        $users = Usuario::find()
                        ->select(['usuario.id', 'usuario.nombres', 'usuario.tipo', 'usuario.url_image', 'usuario.estado'])
                        ->all();
        if($users){
            $response = [
                'success'=>true,
                'users' => $users
            ];
        }else{
            $response = [
                'success'=>false,
                'message'=>'no hay usuarios'
            ];
        }
        return $response;
    }
    public function actionLogin(){
        $params = Yii::$app->getRequest()->getBodyParams();
        $username = $params['username'];
        $user = Usuario::find()-> where(['username' => $username]) -> one();
        $auth = Yii::$app-> authManager;
        if( $user ){
            $password = $params['password'];
            if(Yii::$app->security->validatePassword($password, $user->password_hash)){
                $role = $auth->getRolesByUser($user -> id);
                $response = [
                    'success' => true,
                    'message' => 'Inicio de sesion correcto',
                    'accessToken' => $user -> access_token,
                    'role' => $role,
                    'id' => $user -> id
                ];
            }else{
                $response = [
                    'success' => false,
                    'message' => 'Usuario o contrasenia incorrectos!',
                ];
            }
        }else{
            $response = [
                'success' => false,
                'message' => 'Username o contrase;a incorrectos!'
            ];
        }
        return $response;
    }
    public function actionTest(){
        return Yii::$app->getSecurity()->generatePasswordHash("cesar");
    }

    /* public function actionGetUsers( $pageSize = 5){
        $query = Usuario::find()
                        ->select(['usuario.id', 'usuario.nombres', 'usuario.tipo', 'usuario.url_image']);
        $pagination = new Pagination([
            'defaultPageSize'=> $pageSize,
            'totalCount' => $query->count()
        ]);
        $users = $query
                    ->offset($pagination->offset)
                    ->limit($pagination -> limit)
                    ->all();
        if($users){
            $currentPage = $pagination->getPage() + 1;
            $totalPages = $pagination->getPageCount();
            $response = [
            'success' => true,
            'message' => 'lista de clientes',
            'pageInfo' => [
                'next' => $currentPage == $totalPages ? null  : $currentPage + 1,
                'previus' => $currentPage == 1 ? null: $currentPage - 1,
                'count' => count($users),
                'page' => $currentPage,
                'start' => $pagination->getOffset(),
                'totalPages' => $totalPages,
            ],
            'users' => $users
            ];
        }else{
            $response = [
                'success' => false,
                'message' => 'No existen usuarios',
                'users' => $users
            ];
        }
        return $response;
    } */
    public function actionDisableUser( $idUser ){
        $user = Usuario::findOne($idUser);
        if($user){
            $user -> estado = 'Inactivo';
            if($user -> save()){
                $response = [
                    'success' => true,
                    'message' => 'Usuario Innhabilitado',
                    'user' => $user
                ];
            }
        }else{
            $response = [
                'success' => false,
                'message' => 'No existe usuario',
                'user' => []
            ];
        }
        return $response;
    }
}
