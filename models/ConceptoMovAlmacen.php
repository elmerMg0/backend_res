<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "concepto_mov_almacen".
 *
 * @property int $id
 * @property string $descripcion
 * @property string $clave
 * @property string $tipo
 * @property bool $estado
 *
 * @property MovimientoAlmacen[] $movimientoAlmacens
 */
class ConceptoMovAlmacen extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'concepto_mov_almacen';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descripcion', 'clave', 'tipo'], 'required'],
            [['estado'], 'boolean'],
            [['descripcion'], 'string', 'max' => 50],
            [['clave'], 'string', 'max' => 5],
            [['tipo'], 'string', 'max' => 15],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'descripcion' => 'Descripcion',
            'clave' => 'Clave',
            'tipo' => 'Tipo',
            'estado' => 'Estado',
        ];
    }

    /**
     * Gets query for [[MovimientoAlmacens]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMovimientoAlmacens()
    {
        return $this->hasMany(MovimientoAlmacen::class, ['concepto_mov_almacen_id' => 'id']);
    }
}
