<?php

namespace app\models;

use Firebase\JWT\ExpiredException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Yii;

class User extends \yii\db\ActiveRecord implements \yii\web\IdentityInterface
{


    public static function tableName()	
    {    	
    return 'usuario';	
    }


 


    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $user = User::findOne(['access_token' => $token]);     	
        if ($user) {         	
            if (!$user->verifyExpiredToken()) {             	
                $user->access_token = null;             	
                return new static($user);         	
             }     	
        }     	
        return null;  
    }

    private function verifyExpiredToken() {     	

        $key = Yii::$app->params['keyuser'];
        // Obtiene el token del usuario
        try {
            $token = $this->access_token;     
            $decoded = JWT::decode($token, new Key($key, 'HS256'));
            return time() > $decoded ->exp;
            // Token válido
        } catch (ExpiredException $e) {
            return [
                'success' => false,
                'message' => 'El token ha expirado',
            ]   ;
            // Token expirado, maneja la expiración aquí
        }
        return 1;
    }
        
    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
       

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->authKey;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return $this->password === $password;
    }
}
