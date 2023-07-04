<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "detalle_venta_imprimir".
 *
 * @property int $id
 * @property int $cantidad
 * @property int|null $producto_id
 */
class DetalleVentaImprimir extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'detalle_venta_imprimir';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cantidad'], 'required'],
            [['cantidad', 'producto_id'], 'default', 'value' => null],
            [['cantidad', 'producto_id'], 'integer'],
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
            'producto_id' => 'Producto ID',
        ];
    }
}
