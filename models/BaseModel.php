<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "basemodel".
 *
 * @property integer $id
 * @property integer $created_by 
 * @property date $created
 * @property integer $modified_by 
 * @property date $modified
 * @property integer $deleted soft delete 
 *
 */
class BaseModel extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'created_by' => 'Creato da',
            'created' => 'Creato il',
            'modified_by' => 'Modificato da',
            'modified' => 'Modificato il',
            'deleted' => 'Cancellato'
        ];
    }

}