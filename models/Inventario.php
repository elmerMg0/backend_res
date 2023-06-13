<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "inventario".
 *
 * @property int $id
 * @property string $fecha
 * @property int $product_id
 * @property int|null $total
 * @property int|null $actual
 * @property string|null $diferencia
 *
 * @property Producto $product
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
            [['fecha', 'product_id'], 'required'],
            [['fecha'], 'safe'],
            [['product_id', 'total', 'actual'], 'default', 'value' => null],
            [['product_id', 'total', 'actual'], 'integer'],
            [['diferencia'], 'string'],
            [['product_id'], 'exist', 'skipOnError' => true, 'targetClass' => Producto::class, 'targetAttribute' => ['product_id' => 'id']],
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
            'product_id' => 'Product ID',
            'total' => 'Total',
            'actual' => 'Actual',
            'diferencia' => 'Diferencia',
        ];
    }

    /**
     * Gets query for [[Product]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProduct()
    {
        return $this->hasOne(Producto::class, ['id' => 'product_id']);
    }
}
