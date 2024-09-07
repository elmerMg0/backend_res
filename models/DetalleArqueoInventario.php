<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "detalle_arqueo_inventario".
 *
 * @property int $id
 * @property string|null $nota
 * @property int $insumo_id
 * @property int $arqueo_inventario_id
 * @property float $costo_unitario
 * @property float $teorico_almacen
 * @property float $fisico_almacen
 *
 * @property ArqueoInventario $arqueoInventario
 * @property Insumo $insumo
 */
class DetalleArqueoInventario extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'detalle_arqueo_inventario';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['insumo_id', 'arqueo_inventario_id', 'costo_unitario', 'teorico_almacen', 'fisico_almacen'], 'required'],
            [['insumo_id', 'arqueo_inventario_id'], 'default', 'value' => null],
            [['insumo_id', 'arqueo_inventario_id'], 'integer'],
            [['costo_unitario', 'teorico_almacen', 'fisico_almacen'], 'number'],
            [['nota'], 'string', 'max' => 80],
            [['arqueo_inventario_id'], 'exist', 'skipOnError' => true, 'targetClass' => ArqueoInventario::class, 'targetAttribute' => ['arqueo_inventario_id' => 'id']],
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
            'nota' => 'Nota',
            'insumo_id' => 'Insumo ID',
            'arqueo_inventario_id' => 'Arqueo Inventario ID',
            'costo_unitario' => 'Costo Unitario',
            'teorico_almacen' => 'Teorico Almacen',
            'fisico_almacen' => 'Fisico Almacen',
        ];
    }

    /**
     * Gets query for [[ArqueoInventario]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getArqueoInventario()
    {
        return $this->hasOne(ArqueoInventario::class, ['id' => 'arqueo_inventario_id']);
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
