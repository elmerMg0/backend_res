<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "mesa".
 *
 * @property int $id
 * @property string $estado
 * @property bool $habilitado
 * @property int $area_venta_id
 * @property string $nombre
 * @property string $tipo
 *
 * @property AreaVenta $areaVenta
 * @property Venta[] $ventas
 */
class Mesa extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mesa';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['habilitado'], 'boolean'],
            [['area_venta_id', 'nombre', 'tipo'], 'required'],
            [['area_venta_id'], 'default', 'value' => null],
            [['area_venta_id'], 'integer'],
            [['estado'], 'string', 'max' => 25],
            [['nombre'], 'string', 'max' => 20],
            [['tipo'], 'string', 'max' => 15],
            [['area_venta_id'], 'exist', 'skipOnError' => true, 'targetClass' => AreaVenta::class, 'targetAttribute' => ['area_venta_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'estado' => 'Estado',
            'habilitado' => 'Habilitado',
            'area_venta_id' => 'Area Venta ID',
            'nombre' => 'Nombre',
            'tipo' => 'Tipo',
        ];
    }

    /**
     * Gets query for [[AreaVenta]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAreaVenta()
    {
        return $this->hasOne(AreaVenta::class, ['id' => 'area_venta_id']);
    }

    /**
     * Gets query for [[Ventas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVentas()
    {
        return $this->hasMany(Venta::class, ['mesa_id' => 'id']);
    }
}
