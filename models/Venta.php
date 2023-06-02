<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "venta".
 *
 * @property int $id
 * @property string $fecha
 * @property int|null $cantidad_total
 * @property int|null $cantidad_cancelada
 * @property int $usuario_id
 * @property int $numero_pedido
 * @property int|null $cliente_id
 * @property string $estado
 * @property string|null $tipo_pago
 * @property string|null $tipo
 * @property string|null $direccion
 * @property string|null $descripcion_direccion
 * @property string|null $hora
 * @property string|null $nombre
 * @property string|null $telefono
 * @property string|null $tipo_entrega
 * @property int|null $mesa_id
 *
 * @property Cliente $cliente
 * @property DetalleVenta[] $detalleVentas
 * @property Mesa $mesa
 * @property Usuario $usuario
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
            [['fecha'], 'safe'],
            [['cantidad_total', 'cantidad_cancelada', 'usuario_id', 'numero_pedido', 'cliente_id', 'mesa_id'], 'default', 'value' => null],
            [['cantidad_total', 'cantidad_cancelada', 'usuario_id', 'numero_pedido', 'cliente_id', 'mesa_id'], 'integer'],
            [['usuario_id', 'numero_pedido', 'estado'], 'required'],
            [['tipo_pago', 'direccion', 'descripcion_direccion'], 'string'],
            [['estado', 'nombre'], 'string', 'max' => 50],
            [['tipo', 'tipo_entrega'], 'string', 'max' => 15],
            [['hora'], 'string', 'max' => 20],
            [['telefono'], 'string', 'max' => 12],
            [['cliente_id'], 'exist', 'skipOnError' => true, 'targetClass' => Cliente::class, 'targetAttribute' => ['cliente_id' => 'id']],
            [['mesa_id'], 'exist', 'skipOnError' => true, 'targetClass' => Mesa::class, 'targetAttribute' => ['mesa_id' => 'id']],
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
            'tipo_pago' => 'Tipo Pago',
            'tipo' => 'Tipo',
            'direccion' => 'Direccion',
            'descripcion_direccion' => 'Descripcion Direccion',
            'hora' => 'Hora',
            'nombre' => 'Nombre',
            'telefono' => 'Telefono',
            'tipo_entrega' => 'Tipo Entrega',
            'mesa_id' => 'Mesa ID',
        ];
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
     * Gets query for [[Usuario]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }
}
