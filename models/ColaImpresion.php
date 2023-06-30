<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "cola_impresion".
 *
 * @property int $id
 * @property int|null $venta_id
 * @property bool $estado
 */
class ColaImpresion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cola_impresion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['venta_id'], 'default', 'value' => null],
            [['venta_id'], 'integer'],
            [['estado'], 'required'],
            [['estado'], 'boolean'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'venta_id' => 'Venta ID',
            'estado' => 'Estado',
        ];
    }
}
