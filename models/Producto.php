<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "producto".
 *
 * @property int $id
 * @property string $nombre
 * @property float $precio_venta
 * @property float $costo_compra
 * @property string|null $descripcion
 * @property int $categoria_id
 * @property string|null $url_image
 * @property bool $estado
 * @property bool|null $en_ecommerce
 * @property int|null $area_impresion_id
 * @property bool $favorito
 *
 * @property AreaImpresion $areaImpresion
 * @property Categoria $categoria
 * @property Comentario[] $comentarios
 * @property DetalleVenta[] $detalleVentas
 * @property GrupoModificadores[] $grupoModificadores
 * @property GrupoModificadoresDetalle[] $grupoModificadoresDetalles
 * @property Paquete[] $paquetes
 * @property Paquete[] $paquetes0
 * @property Receta[] $recetas
 */
class Producto extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'producto';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre', 'precio_venta', 'costo_compra', 'categoria_id', 'estado'], 'required'],
            [['precio_venta', 'costo_compra'], 'number'],
            [['categoria_id', 'area_impresion_id'], 'default', 'value' => null],
            [['categoria_id', 'area_impresion_id'], 'integer'],
            [['estado', 'en_ecommerce', 'favorito'], 'boolean'],
            [['nombre', 'url_image'], 'string', 'max' => 50],
            [['descripcion'], 'string', 'max' => 80],
            [['nombre'], 'unique'],
            [['area_impresion_id'], 'exist', 'skipOnError' => true, 'targetClass' => AreaImpresion::class, 'targetAttribute' => ['area_impresion_id' => 'id']],
            [['categoria_id'], 'exist', 'skipOnError' => true, 'targetClass' => Categoria::class, 'targetAttribute' => ['categoria_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'nombre' => 'Nombre',
            'precio_venta' => 'Precio Venta',
            'costo_compra' => 'Costo Compra',
            'descripcion' => 'Descripcion',
            'categoria_id' => 'Categoria ID',
            'url_image' => 'Url Image',
            'estado' => 'Estado',
            'en_ecommerce' => 'En Ecommerce',
            'area_impresion_id' => 'Area Impresion ID',
            'favorito' => 'Favorito',
        ];
    }

    /**
     * Gets query for [[AreaImpresion]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAreaImpresion()
    {
        return $this->hasOne(AreaImpresion::class, ['id' => 'area_impresion_id']);
    }

    /**
     * Gets query for [[Categoria]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategoria()
    {
        return $this->hasOne(Categoria::class, ['id' => 'categoria_id']);
    }

    /**
     * Gets query for [[Comentarios]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getComentarios()
    {
        return $this->hasMany(Comentario::class, ['producto_id' => 'id']);
    }

    /**
     * Gets query for [[DetalleVentas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDetalleVentas()
    {
        return $this->hasMany(DetalleVenta::class, ['producto_id' => 'id']);
    }

    /**
     * Gets query for [[GrupoModificadores]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGrupoModificadores()
    {
        return $this->hasMany(GrupoModificadores::class, ['producto_id' => 'id']);
    }

    /**
     * Gets query for [[GrupoModificadoresDetalles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGrupoModificadoresDetalles()
    {
        return $this->hasMany(GrupoModificadoresDetalle::class, ['producto_id' => 'id']);
    }

    /**
     * Gets query for [[Paquetes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPaquetes()
    {
        return $this->hasMany(Paquete::class, ['producto_id' => 'id']);
    }

    /**
     * Gets query for [[Paquetes0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getPaquetes0()
    {
        return $this->hasMany(Paquete::class, ['producto_parent_id' => 'id']);
    }

    /**
     * Gets query for [[Recetas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRecetas()
    {
        return $this->hasMany(Receta::class, ['producto_id' => 'id']);
    }
}
