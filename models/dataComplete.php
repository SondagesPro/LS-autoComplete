<?php
/**
 * This file is part of reloadAnyResponse plugin
 */
namespace autoComplete\models;
use Yii;
use LSActiveRecord;
class dataComplete extends LSActiveRecord
{
    /**
     * Class autoComplete\models\dataComplete
     *
     * @property integer $id pk
     * @property string $data : the data code
     * @property string $value : the data text
     * @property string $value_simple : same than data text but more simple (no CI etc …)
    */

    /** @inheritdoc */
    public static function model($className=__CLASS__) {
        return parent::model($className);
    }
    /** @inheritdoc */
    public function tableName()
    {
        // todo : find a way to use API 
        return '{{autocomplete_dataComplete}}';
    }

    /** @inheritdoc */
    public function primaryKey()
    {
        return array('id');
    }

    public function rules()
    {
        return array(
            array('id', 'numerical', 'integerOnly'=>true),
            array('data', 'length', 'min' => 1, 'max'=>255),
            array('data', 'required'),
            array('value', 'required'),
            array('value_simple', 'required'), // todo make it auto create
        );
    }

}
