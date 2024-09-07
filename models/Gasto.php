<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "gasto".
 *
 * @property int $id
 * @property string $referencia
 * @property string $fecha
 * @property int $categoria_gasto_id
 * @property int|null $proveedor_id
 * @property float $total
 * @property int $usuario_id
 * @property bool $pagado
 *
 * @property CategoriaGasto $categoriaGasto
 * @property Proveedor $proveedor
 * @property RegistroGasto[] $registroGastos
 * @property Usuario $usuario
 */
class Gasto extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gasto';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['referencia', 'categoria_gasto_id', 'usuario_id'], 'required'],
            [['fecha'], 'safe'],
            [['categoria_gasto_id', 'proveedor_id', 'usuario_id'], 'default', 'value' => null],
            [['categoria_gasto_id', 'proveedor_id', 'usuario_id'], 'integer'],
            [['total'], 'number'],
            [['pagado'], 'boolean'],
            [['referencia'], 'string', 'max' => 50],
            [['categoria_gasto_id'], 'exist', 'skipOnError' => true, 'targetClass' => CategoriaGasto::class, 'targetAttribute' => ['categoria_gasto_id' => 'id']],
            [['proveedor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Proveedor::class, 'targetAttribute' => ['proveedor_id' => 'id']],
            [['usuario_id'], 'exist', 'skipOnError' => true, 'targetClass' => Usuario::class, 'targetAttribute' => ['usuario_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'referencia' => 'Referencia',
            'fecha' => 'Fecha',
            'categoria_gasto_id' => 'Categoria Gasto ID',
            'proveedor_id' => 'Proveedor ID',
            'total' => 'Total',
            'usuario_id' => 'Usuario ID',
            'pagado' => 'Pagado',
        ];
    }

    /**
     * Gets query for [[CategoriaGasto]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCategoriaGasto()
    {
        return $this->hasOne(CategoriaGasto::class, ['id' => 'categoria_gasto_id']);
    }

    /**
     * Gets query for [[Proveedor]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProveedor()
    {
        return $this->hasOne(Proveedor::class, ['id' => 'proveedor_id']);
    }

    /**
     * Gets query for [[RegistroGastos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRegistroGastos()
    {
        return $this->hasMany(RegistroGasto::class, ['gasto_id' => 'id']);
    }

    /**
     * Gets query for [[Usuario]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }
}
