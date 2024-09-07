<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tipo_pago".
 *
 * @property int $id
 * @property string $descripcion
 * @property bool $estado
 * @property int $grupo_tipo_pago_id
 */
class TipoPago extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tipo_pago';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descripcion', 'estado', 'grupo_tipo_pago_id'], 'required'],
            [['estado'], 'boolean'],
            [['grupo_tipo_pago_id'], 'default', 'value' => null],
            [['grupo_tipo_pago_id'], 'integer'],
            [['descripcion'], 'string', 'max' => 20],
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
            'estado' => 'Estado',
            'grupo_tipo_pago_id' => 'Grupo Tipo Pago ID',
        ];
    }
}
