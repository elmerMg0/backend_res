<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "venta".
 *
 * @property int $id
 * @property string $fecha
 * @property float|null $cantidad_total
 * @property float|null $cantidad_cancelada
 * @property int $usuario_id
 * @property int $numero_pedido
 * @property int $cliente_id
 * @property string $estado
 * @property string|null $tipo
 * @property int|null $mesa_id
 * @property string|null $nota
 * @property string|null $info_cliente
 * @property string $fecha_cierre
 * @property int $area_venta_id
 * @property string|null $nro_mesa
 * @property int $tipo_pago_id
 *
 * @property AreaVenta $areaVenta
 * @property Cliente $cliente
 * @property ColaImpresion[] $colaImpresions
 * @property DetalleVenta[] $detalleVentas
 * @property Mesa $mesa
 * @property TipoPago $tipoPago
 * @property Usuario $usuario
 * @property VentaAreaImpresion[] $ventaAreaImpresions
 * @property VentaDescuento[] $ventaDescuentos
 */
class Venta extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'venta';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fecha', 'info_cliente', 'fecha_cierre'], 'safe'],
            [['cantidad_total', 'cantidad_cancelada'], 'number'],
            [['usuario_id', 'numero_pedido', 'cliente_id', 'estado', 'area_venta_id'], 'required'],
            [['usuario_id', 'numero_pedido', 'cliente_id', 'mesa_id', 'area_venta_id', 'tipo_pago_id'], 'default', 'value' => null],
            [['usuario_id', 'numero_pedido', 'cliente_id', 'mesa_id', 'area_venta_id', 'tipo_pago_id'], 'integer'],
            [['nota'], 'string'],
            [['estado'], 'string', 'max' => 50],
            [['tipo'], 'string', 'max' => 15],
            [['nro_mesa'], 'string', 'max' => 20],
            [['area_venta_id'], 'exist', 'skipOnError' => true, 'targetClass' => AreaVenta::class, 'targetAttribute' => ['area_venta_id' => 'id']],
            [['cliente_id'], 'exist', 'skipOnError' => true, 'targetClass' => Cliente::class, 'targetAttribute' => ['cliente_id' => 'id']],
            [['mesa_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mesa::class, 'targetAttribute' => ['mesa_id' => 'id']],
            [['tipo_pago_id'], 'exist', 'skipOnError' => true, 'targetClass' => TipoPago::class, 'targetAttribute' => ['tipo_pago_id' => 'id']],
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
            'fecha' => 'Fecha',
            'cantidad_total' => 'Cantidad Total',
            'cantidad_cancelada' => 'Cantidad Cancelada',
            'usuario_id' => 'Usuario ID',
            'numero_pedido' => 'Numero Pedido',
            'cliente_id' => 'Cliente ID',
            'estado' => 'Estado',
            'tipo' => 'Tipo',
            'mesa_id' => 'Mesa ID',
            'nota' => 'Nota',
            'info_cliente' => 'Info Cliente',
            'fecha_cierre' => 'Fecha Cierre',
            'area_venta_id' => 'Area Venta ID',
            'nro_mesa' => 'Nro Mesa',
            'tipo_pago_id' => 'Tipo Pago ID',
        ];
    }

    /**
     * Gets query for [[AreaVenta]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAreaVenta()
    {
        return $this->hasOne(AreaVenta::class, ['id' => 'area_venta_id']);
    }

    /**
     * Gets query for [[Cliente]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCliente()
    {
        return $this->hasOne(Cliente::class, ['id' => 'cliente_id']);
    }

    /**
     * Gets query for [[ColaImpresions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getColaImpresions()
    {
        return $this->hasMany(ColaImpresion::class, ['venta_id' => 'id']);
    }

    /**
     * Gets query for [[DetalleVentas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDetalleVentas()
    {
        return $this->hasMany(DetalleVenta::class, ['venta_id' => 'id']);
    }

    /**
     * Gets query for [[Mesa]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMesa()
    {
        return $this->hasOne(Mesa::class, ['id' => 'mesa_id']);
    }

    /**
     * Gets query for [[TipoPago]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTipoPago()
    {
        return $this->hasOne(TipoPago::class, ['id' => 'tipo_pago_id']);
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

    /**
     * Gets query for [[VentaAreaImpresions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVentaAreaImpresions()
    {
        return $this->hasMany(VentaAreaImpresion::class, ['venta_id' => 'id']);
    }

    /**
     * Gets query for [[VentaDescuentos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getVentaDescuentos()
    {
        return $this->hasMany(VentaDescuento::class, ['venta_id' => 'id']);
    }
}
