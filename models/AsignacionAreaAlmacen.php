<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "asignacion_area_almacen".
 *
 * @property int $id
 * @property int $receta_id
 * @property int $almacen_id
 * @property int $area_venta_id
 */
class AsignacionAreaAlmacen extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'asignacion_area_almacen';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['receta_id', 'almacen_id', 'area_venta_id'], 'required'],
            [['receta_id', 'almacen_id', 'area_venta_id'], 'default', 'value' => null],
            [['receta_id', 'almacen_id', 'area_venta_id'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'receta_id' => 'Receta ID',
            'almacen_id' => 'Almacen ID',
            'area_venta_id' => 'Area Venta ID',
        ];
    }
}
