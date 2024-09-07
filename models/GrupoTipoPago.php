<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "grupo_tipo_pago".
 *
 * @property int $id
 * @property string $descripcion
 */
class GrupoTipoPago extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'grupo_tipo_pago';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descripcion'], 'required'],
            [['descripcion'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'descripcion' => 'Descripcion',
        ];
    }
}
