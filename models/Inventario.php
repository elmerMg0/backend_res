<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "inventario".
 *
 * @property int $id
 * @property int $insumo_id
 * @property int $almacen_id
 * @property float $cantidad
 *
 * @property Almacen $almacen
 * @property Insumo $insumo
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
            [['insumo_id', 'almacen_id', 'cantidad'], 'required'],
            [['insumo_id', 'almacen_id'], 'default', 'value' => null],
            [['insumo_id', 'almacen_id'], 'integer'],
            [['cantidad'], 'number'],
            [['almacen_id'], 'exist', 'skipOnError' => true, 'targetClass' => Almacen::class, 'targetAttribute' => ['almacen_id' => 'id']],
            [['insumo_id'], 'exist', 'skipOnError' => true, 'targetClass' => Insumo::class, 'targetAttribute' => ['insumo_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'insumo_id' => 'Insumo ID',
            'almacen_id' => 'Almacen ID',
            'cantidad' => 'Cantidad',
        ];
    }

    /**
     * Gets query for [[Almacen]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAlmacen()
    {
        return $this->hasOne(Almacen::class, ['id' => 'almacen_id']);
    }

    /**
     * Gets query for [[Insumo]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInsumo()
    {
        return $this->hasOne(Insumo::class, ['id' => 'insumo_id']);
    }
}
