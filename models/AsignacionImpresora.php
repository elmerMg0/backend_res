<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "asignacion_impresora".
 *
 * @property int $id
 * @property string $printer_name
 * @property int|null $area_impresion_id
 *
 * @property AreaImpresion $areaImpresion
 */
class AsignacionImpresora extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'asignacion_impresora';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['printer_name'], 'required'],
            [['area_impresion_id'], 'default', 'value' => null],
            [['area_impresion_id'], 'integer'],
            [['printer_name'], 'string', 'max' => 50],
            [['area_impresion_id'], 'exist', 'skipOnError' => true, 'targetClass' => AreaImpresion::class, 'targetAttribute' => ['area_impresion_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'printer_name' => 'Printer Name',
            'area_impresion_id' => 'Area Impresion ID',
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
}
