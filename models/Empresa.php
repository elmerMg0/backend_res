<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "empresa".
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $email
 * @property int|null $phone
 * @property int|null $celular
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
            [['nombre'], 'required'],
            [['phone', 'celular', 'nit'], 'default', 'value' => null],
            [['phone', 'celular', 'nit'], 'integer'],
            [['direccion', 'dias_atencion', 'horario_atencion'], 'string'],
            [['nombre'], 'string', 'max' => 50],
            [['email'], 'string', 'max' => 80],
            [['image_url'], 'string', 'max' => 100],
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
