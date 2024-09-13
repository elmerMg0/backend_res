<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "movimiento_almacen".
 *
 * @property int $id
 * @property string $fecha
 * @property int $almacen_id
 * @property bool $estado
 * @property int $usuario_id
 * @property int|null $concepto_mov_almacen_id
 * @property string|null $nota
 * @property float|null $total
 *
 * @property Almacen $almacen
 * @property ConceptoMovAlmacen $conceptoMovAlmacen
 * @property MovimientoAlmacenInsumo[] $movimientoAlmacenInsumos
 * @property MovimientoAlmacenPresentacion[] $movimientoAlmacenPresentacions
 * @property Usuario $usuario
 */
class MovimientoAlmacen extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'movimiento_almacen';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fecha'], 'safe'],
            [['almacen_id', 'usuario_id'], 'required'],
            [['almacen_id', 'usuario_id', 'concepto_mov_almacen_id'], 'default', 'value' => null],
            [['almacen_id', 'usuario_id', 'concepto_mov_almacen_id'], 'integer'],
            [['estado'], 'boolean'],
            [['total'], 'number'],
            [['nota'], 'string', 'max' => 80],
            [['almacen_id'], 'exist', 'skipOnError' => true, 'targetClass' => Almacen::class, 'targetAttribute' => ['almacen_id' => 'id']],
            [['concepto_mov_almacen_id'], 'exist', 'skipOnError' => true, 'targetClass' => ConceptoMovAlmacen::class, 'targetAttribute' => ['concepto_mov_almacen_id' => 'id']],
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
            'almacen_id' => 'Almacen ID',
            'estado' => 'Estado',
            'usuario_id' => 'Usuario ID',
            'concepto_mov_almacen_id' => 'Concepto Mov Almacen ID',
            'nota' => 'Nota',
            'total' => 'Total',
        ];
    }

    /**
     * Gets query for [[Almacen]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getAlmacen()
    {
        return $this->hasOne(Almacen::class, ['id' => 'almacen_id']);
    }

    /**
     * Gets query for [[ConceptoMovAlmacen]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getConceptoMovAlmacen()
    {
        return $this->hasOne(ConceptoMovAlmacen::class, ['id' => 'concepto_mov_almacen_id']);
    }

    /**
     * Gets query for [[MovimientoAlmacenInsumos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMovimientoAlmacenInsumos()
    {
        return $this->hasMany(MovimientoAlmacenInsumo::class, ['movimiento_almacen_id' => 'id']);
    }

    /**
     * Gets query for [[MovimientoAlmacenPresentacions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMovimientoAlmacenPresentacions()
    {
        return $this->hasMany(MovimientoAlmacenPresentacion::class, ['movimiento_almacen_id' => 'id']);
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
