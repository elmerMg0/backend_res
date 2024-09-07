<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "inventario_pres".
 *
 * @property int $id
 * @property int $presentacion_id
 * @property int $almacen_id
 * @property float $cantidad
 */
class InventarioPres extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'inventario_pres';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['presentacion_id', 'almacen_id', 'cantidad'], 'required'],
            [['presentacion_id', 'almacen_id'], 'default', 'value' => null],
            [['presentacion_id', 'almacen_id'], 'integer'],
            [['cantidad'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'presentacion_id' => 'Presentacion ID',
            'almacen_id' => 'Almacen ID',
            'cantidad' => 'Cantidad',
        ];
    }
}
