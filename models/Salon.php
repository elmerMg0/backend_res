<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "salon".
 *
 * @property int $id
 * @property bool $estado
 * @property int $nro_filas
 * @property int $nro_columnas
 * @property string $nombre
 * @property string|null $url_image
 *
 * @property Mesa[] $mesas
 */
class Salon extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'salon';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['estado'], 'boolean'],
            [['nro_filas', 'nro_columnas', 'nombre'], 'required'],
            [['nro_filas', 'nro_columnas'], 'default', 'value' => null],
            [['nro_filas', 'nro_columnas'], 'integer'],
            [['nombre'], 'string', 'max' => 20],
            [['url_image'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'estado' => 'Estado',
            'nro_filas' => 'Nro Filas',
            'nro_columnas' => 'Nro Columnas',
            'nombre' => 'Nombre',
            'url_image' => 'Url Image',
        ];
    }

    /**
     * Gets query for [[Mesas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMesas()
    {
        return $this->hasMany(Mesa::class, ['salon_id' => 'id']);
    }
}
