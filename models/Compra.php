<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "compra".
 *
 * @property int $id
 * @property int $proveedor_id
 * @property string $fecha
 * @property string $vencimiento
 * @property string $referencia
 * @property int|null $empresa_id
 * @property string|null $folio_factura
 * @property float $importe
 * @property string $estado
 * @property int $usuario_id
 *
 * @property DetalleCompra[] $detalleCompras
 * @property Usuario $usuario
 */
class Compra extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'compra';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['proveedor_id', 'referencia', 'importe', 'estado', 'usuario_id'], 'required'],
            [['proveedor_id', 'empresa_id', 'usuario_id'], 'default', 'value' => null],
            [['proveedor_id', 'empresa_id', 'usuario_id'], 'integer'],
            [['fecha', 'vencimiento'], 'safe'],
            [['importe'], 'number'],
            [['referencia'], 'string', 'max' => 80],
            [['folio_factura'], 'string', 'max' => 25],
            [['estado'], 'string', 'max' => 20],
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
            'proveedor_id' => 'Proveedor ID',
            'fecha' => 'Fecha',
            'vencimiento' => 'Vencimiento',
            'referencia' => 'Referencia',
            'empresa_id' => 'Empresa ID',
            'folio_factura' => 'Folio Factura',
            'importe' => 'Importe',
            'estado' => 'Estado',
            'usuario_id' => 'Usuario ID',
        ];
    }

    /**
     * Gets query for [[DetalleCompras]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getDetalleCompras()
    {
        return $this->hasMany(DetalleCompra::class, ['compra_id' => 'id']);
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
