<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "presentacion".
 *
 * @property int $id
 * @property int $insumo_id
 * @property string $descripcion
 * @property float $ultimo_costo
 * @property float $costo_promedio
 * @property int $proveedor_id
 * @property float $rendimiento
 * @property bool $estado
 * @property string|null $ubicacion
 * @property int|null $stock_minimo
 * @property int|null $stock_maximo
 *
 * @property DetalleCompra[] $detalleCompras
 * @property Insumo $insumo
 * @property InventarioPres[] $inventarioPres
 * @property Proveedor $proveedor
 * @property TraspasoAlmacenPresentacion[] $traspasoAlmacenPresentacions
 */
class Presentacion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'presentacion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['insumo_id', 'descripcion', 'proveedor_id', 'rendimiento'], 'required'],
            [['insumo_id', 'proveedor_id', 'stock_minimo', 'stock_maximo'], 'default', 'value' => null],
            [['insumo_id', 'proveedor_id', 'stock_minimo', 'stock_maximo'], 'integer'],
            [['ultimo_costo', 'costo_promedio', 'rendimiento'], 'number'],
            [['estado'], 'boolean'],
            [['descripcion', 'ubicacion'], 'string', 'max' => 50],
            [['insumo_id'], 'exist', 'skipOnError' => true, 'targetClass' => Insumo::class, 'targetAttribute' => ['insumo_id' => 'id']],
            [['proveedor_id'], 'exist', 'skipOnError' => true, 'targetClass' => Proveedor::class, 'targetAttribute' => ['proveedor_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'insumo_id' => 'Insumo ID',
            'descripcion' => 'Descripcion',
            'ultimo_costo' => 'Ultimo Costo',
            'costo_promedio' => 'Costo Promedio',
            'proveedor_id' => 'Proveedor ID',
            'rendimiento' => 'Rendimiento',
            'estado' => 'Estado',
            'ubicacion' => 'Ubicacion',
            'stock_minimo' => 'Stock Minimo',
            'stock_maximo' => 'Stock Maximo',
        ];
    }

    /**
     * Gets query for [[DetalleCompras]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDetalleCompras()
    {
        return $this->hasMany(DetalleCompra::class, ['presentacion_id' => 'id']);
    }

    /**
     * Gets query for [[Insumo]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInsumo()
    {
        return $this->hasOne(Insumo::class, ['id' => 'insumo_id']);
    }

    /**
     * Gets query for [[InventarioPres]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInventarioPres()
    {
        return $this->hasMany(InventarioPres::class, ['presentacion_id' => 'id']);
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
     * Gets query for [[TraspasoAlmacenPresentacions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTraspasoAlmacenPresentacions()
    {
        return $this->hasMany(TraspasoAlmacenPresentacion::class, ['presentacion_id' => 'id']);
    }
}
