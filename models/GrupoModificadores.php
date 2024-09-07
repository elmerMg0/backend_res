<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "grupo_modificadores".
 *
 * @property int $id
 * @property int $secuencia_boton
 * @property int $modif_incluidos_precio
 * @property int $modif_maximos
 * @property bool $forzar_captura
 * @property int $catalogo_grupo_modificadores_id
 * @property int $producto_id
 *
 * @property CatalogoGrupoModificadores $catalogoGrupoModificadores
 * @property GrupoModificadoresDetalle[] $grupoModificadoresDetalles
 * @property Producto $producto
 */
class GrupoModificadores extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'grupo_modificadores';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['secuencia_boton', 'modif_incluidos_precio', 'modif_maximos', 'catalogo_grupo_modificadores_id', 'producto_id'], 'required'],
            [['secuencia_boton', 'modif_incluidos_precio', 'modif_maximos', 'catalogo_grupo_modificadores_id', 'producto_id'], 'default', 'value' => null],
            [['secuencia_boton', 'modif_incluidos_precio', 'modif_maximos', 'catalogo_grupo_modificadores_id', 'producto_id'], 'integer'],
            [['forzar_captura'], 'boolean'],
            [['catalogo_grupo_modificadores_id'], 'exist', 'skipOnError' => true, 'targetClass' => CatalogoGrupoModificadores::class, 'targetAttribute' => ['catalogo_grupo_modificadores_id' => 'id']],
            [['producto_id'], 'exist', 'skipOnError' => true, 'targetClass' => Producto::class, 'targetAttribute' => ['producto_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'secuencia_boton' => 'Secuencia Boton',
            'modif_incluidos_precio' => 'Modif Incluidos Precio',
            'modif_maximos' => 'Modif Maximos',
            'forzar_captura' => 'Forzar Captura',
            'catalogo_grupo_modificadores_id' => 'Catalogo Grupo Modificadores ID',
            'producto_id' => 'Producto ID',
        ];
    }

    /**
     * Gets query for [[CatalogoGrupoModificadores]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCatalogoGrupoModificadores()
    {
        return $this->hasOne(CatalogoGrupoModificadores::class, ['id' => 'catalogo_grupo_modificadores_id']);
    }

    /**
     * Gets query for [[GrupoModificadoresDetalles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGrupoModificadoresDetalles()
    {
        return $this->hasMany(GrupoModificadoresDetalle::class, ['grupo_modificadores_id' => 'id']);
    }

    /**
     * Gets query for [[Producto]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProducto()
    {
        return $this->hasOne(Producto::class, ['id' => 'producto_id']);
    }
}
