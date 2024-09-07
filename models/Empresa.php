<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "empresa".
 *
 * @property int $id
 * @property string|null $nombre
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $celular
 * @property int|null $nit
 * @property string|null $image_url
 * @property string|null $direccion
 * @property string|null $dias_atencion
 * @property string|null $horario_atencion
 */
class Empresa extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'empresa';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['email', 'direccion', 'dias_atencion', 'horario_atencion'], 'string'],
            [['nit'], 'default', 'value' => null],
            [['nit'], 'integer'],
            [['nombre'], 'string', 'max' => 50],
            [['phone', 'celular'], 'string', 'max' => 8],
            [['image_url'], 'string', 'max' => 25],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
            'email' => 'Email',
            'phone' => 'Phone',
            'celular' => 'Celular',
            'nit' => 'Nit',
            'image_url' => 'Image Url',
            'direccion' => 'Direccion',
            'dias_atencion' => 'Dias Atencion',
            'horario_atencion' => 'Horario Atencion',
        ];
    }
}
