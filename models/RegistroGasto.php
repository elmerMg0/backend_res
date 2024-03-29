<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "registro_gasto".
 *
 * @property int $id
 * @property int $cantidad
 * @property float $precio_unitario
 * @property string $fecha
 * @property string $estado
 * @property int $gasto_id
 * @property float $total
 *
 * @property Gasto $gasto
 */
class RegistroGasto extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'registro_gasto';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cantidad', 'precio_unitario', 'estado', 'gasto_id', 'total'], 'required'],
            [['cantidad', 'gasto_id'], 'default', 'value' => null],
            [['cantidad', 'gasto_id'], 'integer'],
            [['precio_unitario', 'total'], 'number'],
            [['fecha'], 'safe'],
            [['estado'], 'string', 'max' => 12],
            [['gasto_id'], 'exist', 'skipOnError' => true, 'targetClass' => Gasto::class, 'targetAttribute' => ['gasto_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cantidad' => 'Cantidad',
            'precio_unitario' => 'Precio Unitario',
            'fecha' => 'Fecha',
            'estado' => 'Estado',
            'gasto_id' => 'Gasto ID',
            'total' => 'Total',
        ];
    }

    /**
     * Gets query for [[Gasto]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGasto()
    {
        return $this->hasOne(Gasto::class, ['id' => 'gasto_id']);
    }
}
