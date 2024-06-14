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
 * @property string|null $tipo_pago
 * @property string|null $tipo
 * @property int $mesa_id
 * @property bool|null $finalizado
 * @property string|null $nota
 * @property bool|null $finalizado_bar
 * @property string|null $info_cliente
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
            [['fecha', 'info_cliente'], 'safe'],
            [['cantidad_total', 'cantidad_cancelada'], 'number'],
            [['usuario_id', 'numero_pedido', 'cliente_id', 'estado', 'mesa_id'], 'required'],
            [['usuario_id', 'numero_pedido', 'cliente_id', 'mesa_id'], 'default', 'value' => null],
            [['usuario_id', 'numero_pedido', 'cliente_id', 'mesa_id'], 'integer'],
            [['tipo_pago', 'nota'], 'string'],
            [['finalizado', 'finalizado_bar'], 'boolean'],
            [['estado'], 'string', 'max' => 50],
            [['tipo'], 'string', 'max' => 15],
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
            'mesa_id' => 'Mesa ID',
            'finalizado' => 'Finalizado',
            'nota' => 'Nota',
            'finalizado_bar' => 'Finalizado Bar',
            'info_cliente' => 'Info Cliente',
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
