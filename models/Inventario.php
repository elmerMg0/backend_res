<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "inventario".
 *
 * @property int $id
 * @property string $fecha
 * @property int $producto_id
 * @property int|null $total
 * @property int|null $actual
 * @property string|null $diferencia
 *
 * @property Producto $producto
 */
class Inventario extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'inventario';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fecha', 'producto_id'], 'required'],
            [['fecha'], 'safe'],
            [['producto_id', 'total', 'actual'], 'default', 'value' => null],
            [['producto_id', 'total', 'actual'], 'integer'],
            [['diferencia'], 'string'],
            [['producto_id'], 'exist', 'skipOnError' => true, 'targetClass' => Producto::class, 'targetAttribute' => ['producto_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fecha' => 'Fecha',
            'producto_id' => 'Producto ID',
            'total' => 'Total',
            'actual' => 'Actual',
            'diferencia' => 'Diferencia',
        ];
    }

    /**
     * Gets query for [[Producto]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProducto()
    {
        return $this->hasOne(Producto::class, ['id' => 'producto_id']);
    }
}
