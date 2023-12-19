<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\models\Usuario;
use app\models\Periodo;
use Firebase\JWT\JWT;

class UserController extends Controller
{
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        $behaviors['verbs'] = [
            'class' => \yii\filters\VerbFilter::class,
            'actions' => [
                'login' => ['POST'],
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
    public function actionLogin()
    {
        $params = Yii::$app->getRequest()->getBodyParams();
        $username = $params['username'];
        $user = Usuario::find()->where(['username' => $username])->one();
        $auth = Yii::$app->authManager;
        if ($user) {
            $password = $params['password'];
            if (Yii::$app->security->validatePassword($password, $user->password_hash)) {
                if ($user->estado === 'Activo') {
                    $keyuser = Yii::$app->params['keyuser'];
                    $currentTimestamp = time();
                    //$expirationTimestamp = $currentTimestamp + (2 * 24 * 60 * 60);
                    $expirationTimestamp = $currentTimestamp + (200);

                    $payload = [
                        'iss' => 'https://jevesoftd.tech/',
                        'aud' => 'https://dimasresprodbeta.netlify.app/',
                        'iat' => $currentTimestamp,
                        'nbf' => $currentTimestamp,
                        'exp' => $expirationTimestamp,
                    ];

                    $jwt = JWT::encode($payload, $keyuser, 'HS256');
                    $user -> access_token = $jwt;
                    $user -> save();

                    $role = $auth->getRolesByUser($user->id);
                    $period = Periodo::find()
                        ->where(['usuario_id' => $user->id])
                        ->andWhere(['estado' => true])
                        ->one();
                    if ($period) {
                        $period = $period->id;
                    }
                    $response = [
                        'success' => true,
                        'message' => 'Inicio de sesion correcto',
                        'accessToken' => $jwt,
                        'role' => $role,
                        'id' => $user->id,
                        'period' => $period,
                        'tipo' => $user -> tipo
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => 'Cuenta desactivada',
                    ];
                }
            } else {
                $response = [
                    'success' => false,
                    'message' => 'Usuario o contraseña incorrectos!',
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'Username o contraseña incorrectos!'
            ];
        }
        return $response;
    }
}
