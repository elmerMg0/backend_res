<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "almacen".
 *
 * @property int $id
 * @property string $descripcion
 * @property string $tipo
 * @property string|null $empresa_id
 * @property bool $estado
 *
 * @property AsignacionAreaAlmacen[] $asignacionAreaAlmacens
 * @property Inventario[] $inventarios
 */
class Almacen extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'almacen';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descripcion', 'tipo'], 'required'],
            [['empresa_id'], 'string'],
            [['estado'], 'boolean'],
            [['descripcion'], 'string', 'max' => 50],
            [['tipo'], 'string', 'max' => 20],
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
            'tipo' => 'Tipo',
            'empresa_id' => 'Empresa ID',
            'estado' => 'Estado',
        ];
    }

    /**
     * Gets query for [[AsignacionAreaAlmacens]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAsignacionAreaAlmacens()
    {
        return $this->hasMany(AsignacionAreaAlmacen::class, ['almacen_id' => 'id']);
    }

    /**
     * Gets query for [[Inventarios]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInventarios()
    {
        return $this->hasMany(Inventario::class, ['almacen_id' => 'id']);
    }
}
