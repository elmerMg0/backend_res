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
 * @property int|null $cliente_id
 * @property string $estado
 * @property string|null $tipo_pago
 * @property string|null $tipo
 * @property string|null $hora
 * @property string|null $tipo_entrega
 * @property int|null $mesa_id
 * @property bool|null $finalizado
 *
 * @property Cliente $cliente
 * @property ColaImpresion[] $colaImpresions
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
            [['cantidad_total', 'cantidad_cancelada'], 'number'],
            [['usuario_id', 'numero_pedido', 'estado'], 'required'],
            [['usuario_id', 'numero_pedido', 'cliente_id', 'mesa_id'], 'default', 'value' => null],
            [['usuario_id', 'numero_pedido', 'cliente_id', 'mesa_id'], 'integer'],
            [['tipo_pago'], 'string'],
            [['finalizado'], 'boolean'],
            [['estado'], 'string', 'max' => 50],
            [['tipo', 'tipo_entrega'], 'string', 'max' => 15],
            [['hora'], 'string', 'max' => 20],
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
            'hora' => 'Hora',
            'tipo_entrega' => 'Tipo Entrega',
            'mesa_id' => 'Mesa ID',
            'finalizado' => 'Finalizado',
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
     * Gets query for [[Usuario]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUsuario()
    {
        return $this->hasOne(Usuario::class, ['id' => 'usuario_id']);
    }
}
