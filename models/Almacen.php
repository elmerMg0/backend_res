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
 * @property DetalleCompra[] $detalleCompras
 * @property InventarioPres[] $inventarioPres
 * @property Inventario[] $inventarios
 * @property MovimientoAlmacen[] $movimientoAlmacens
 * @property TraspasoAlmacen[] $traspasoAlmacens
 * @property TraspasoAlmacen[] $traspasoAlmacens0
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
     * Gets query for [[DetalleCompras]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDetalleCompras()
    {
        return $this->hasMany(DetalleCompra::class, ['almacen_id' => 'id']);
    }

    /**
     * Gets query for [[InventarioPres]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInventarioPres()
    {
        return $this->hasMany(InventarioPres::class, ['almacen_id' => 'id']);
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

    /**
     * Gets query for [[MovimientoAlmacens]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMovimientoAlmacens()
    {
        return $this->hasMany(MovimientoAlmacen::class, ['almacen_id' => 'id']);
    }

    /**
     * Gets query for [[TraspasoAlmacens]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTraspasoAlmacens()
    {
        return $this->hasMany(TraspasoAlmacen::class, ['almacen_origen_id' => 'id']);
    }

    /**
     * Gets query for [[TraspasoAlmacens0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTraspasoAlmacens0()
    {
        return $this->hasMany(TraspasoAlmacen::class, ['almacen_destino_id' => 'id']);
    }
}
