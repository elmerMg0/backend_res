<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "categoria".
 *
 * @property int $id
 * @property string $nombre
 * @property string|null $descripcion
 * @property string|null $url_image
 * @property bool $estado
 * @property bool|null $en_ecommerce
 * @property int $clasificacion_grupo_id
 * @property string|null $background
 * @property int|null $categoria_id
 * @property string|null $color
 *
 * @property Categoria $categoria
 * @property Categoria[] $categorias
 * @property ClasificacionGrupo $clasificacionGrupo
 * @property Producto[] $productos
 */
class Categoria extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'categoria';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombre', 'estado', 'clasificacion_grupo_id'], 'required'],
            [['estado', 'en_ecommerce'], 'boolean'],
            [['clasificacion_grupo_id', 'categoria_id'], 'default', 'value' => null],
            [['clasificacion_grupo_id', 'categoria_id'], 'integer'],
            [['nombre', 'url_image'], 'string', 'max' => 50],
            [['descripcion'], 'string', 'max' => 80],
            [['background', 'color'], 'string', 'max' => 7],
            [['categoria_id'], 'exist', 'skipOnError' => true, 'targetClass' => Categoria::class, 'targetAttribute' => ['categoria_id' => 'id']],
            [['clasificacion_grupo_id'], 'exist', 'skipOnError' => true, 'targetClass' => ClasificacionGrupo::class, 'targetAttribute' => ['clasificacion_grupo_id' => 'id']],
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
            'descripcion' => 'Descripcion',
            'url_image' => 'Url Image',
            'estado' => 'Estado',
            'en_ecommerce' => 'En Ecommerce',
            'clasificacion_grupo_id' => 'Clasificacion Grupo ID',
            'background' => 'Background',
            'categoria_id' => 'Categoria ID',
            'color' => 'Color',
        ];
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
     * Gets query for [[Categorias]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategorias()
    {
        return $this->hasMany(Categoria::class, ['categoria_id' => 'id']);
    }

    /**
     * Gets query for [[ClasificacionGrupo]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClasificacionGrupo()
    {
        return $this->hasOne(ClasificacionGrupo::class, ['id' => 'clasificacion_grupo_id']);
    }

    /**
     * Gets query for [[Productos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProductos()
    {
        return $this->hasMany(Producto::class, ['categoria_id' => 'id']);
    }
}
