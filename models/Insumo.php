<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "insumo".
 *
 * @property int $id
 * @property string $descripcion
 * @property float|null $ultimo_costo
 * @property float|null $costo_promedio
 * @property bool $inventariable
 * @property bool|null $alerta_existencias
 * @property int|null $porcentaje_merma
 * @property float|null $ultimo_costo_c_merma
 * @property int $grupo_insumo_id
 * @property int $unidad_medida_id
 * @property bool $estado
 * @property int|null $stock_maximo
 * @property int|null $stock_minimo
 *
 * @property DetalleArqueoInventario[] $detalleArqueoInventarios
 * @property GrupoInsumo $grupoInsumo
 * @property Inventario[] $inventarios
 * @property MovimientoAlmacenInsumo[] $movimientoAlmacenInsumos
 * @property Presentacion[] $presentacions
 * @property Receta[] $recetas
 * @property TraspasoAlmacenInsumo[] $traspasoAlmacenInsumos
 * @property UnidadMedida $unidadMedida
 */
class Insumo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'insumo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['descripcion', 'grupo_insumo_id', 'unidad_medida_id'], 'required'],
            [['ultimo_costo', 'costo_promedio', 'ultimo_costo_c_merma'], 'number'],
            [['inventariable', 'alerta_existencias', 'estado'], 'boolean'],
            [['porcentaje_merma', 'grupo_insumo_id', 'unidad_medida_id', 'stock_maximo', 'stock_minimo'], 'default', 'value' => null],
            [['porcentaje_merma', 'grupo_insumo_id', 'unidad_medida_id', 'stock_maximo', 'stock_minimo'], 'integer'],
            [['descripcion'], 'string', 'max' => 50],
            [['grupo_insumo_id'], 'exist', 'skipOnError' => true, 'targetClass' => GrupoInsumo::class, 'targetAttribute' => ['grupo_insumo_id' => 'id']],
            [['unidad_medida_id'], 'exist', 'skipOnError' => true, 'targetClass' => UnidadMedida::class, 'targetAttribute' => ['unidad_medida_id' => 'id']],
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
            'ultimo_costo' => 'Ultimo Costo',
            'costo_promedio' => 'Costo Promedio',
            'inventariable' => 'Inventariable',
            'alerta_existencias' => 'Alerta Existencias',
            'porcentaje_merma' => 'Porcentaje Merma',
            'ultimo_costo_c_merma' => 'Ultimo Costo C Merma',
            'grupo_insumo_id' => 'Grupo Insumo ID',
            'unidad_medida_id' => 'Unidad Medida ID',
            'estado' => 'Estado',
            'stock_maximo' => 'Stock Maximo',
            'stock_minimo' => 'Stock Minimo',
        ];
    }

    /**
     * Gets query for [[DetalleArqueoInventarios]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDetalleArqueoInventarios()
    {
        return $this->hasMany(DetalleArqueoInventario::class, ['insumo_id' => 'id']);
    }

    /**
     * Gets query for [[GrupoInsumo]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGrupoInsumo()
    {
        return $this->hasOne(GrupoInsumo::class, ['id' => 'grupo_insumo_id']);
    }

    /**
     * Gets query for [[Inventarios]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInventarios()
    {
        return $this->hasMany(Inventario::class, ['insumo_id' => 'id']);
    }

    /**
     * Gets query for [[MovimientoAlmacenInsumos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMovimientoAlmacenInsumos()
    {
        return $this->hasMany(MovimientoAlmacenInsumo::class, ['insumo_id' => 'id']);
    }

    /**
     * Gets query for [[Presentacions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPresentacions()
    {
        return $this->hasMany(Presentacion::class, ['insumo_id' => 'id']);
    }

    /**
     * Gets query for [[Recetas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRecetas()
    {
        return $this->hasMany(Receta::class, ['insumo_id' => 'id']);
    }

    /**
     * Gets query for [[TraspasoAlmacenInsumos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTraspasoAlmacenInsumos()
    {
        return $this->hasMany(TraspasoAlmacenInsumo::class, ['insumo_id' => 'id']);
    }

    /**
     * Gets query for [[UnidadMedida]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUnidadMedida()
    {
        return $this->hasOne(UnidadMedida::class, ['id' => 'unidad_medida_id']);
    }
}
