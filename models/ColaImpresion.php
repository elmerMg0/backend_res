<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "cola_impresion".
 *
 * @property int $id
 * @property int|null $venta_id
 * @property string|null $area
 * @property bool|null $estado
 *
 * @property Venta $venta
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
            [['estado'], 'boolean'],
            [['area'], 'string', 'max' => 20],
            [['venta_id'], 'exist', 'skipOnError' => true, 'targetClass' => Venta::class, 'targetAttribute' => ['venta_id' => 'id']],
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
            'area' => 'Area',
            'estado' => 'Estado',
        ];
    }

    /**
     * Gets query for [[Venta]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVenta()
    {
        return $this->hasOne(Venta::class, ['id' => 'venta_id']);
    }
}
