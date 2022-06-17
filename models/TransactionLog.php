<?php
namespace app\models;

use app\common\helpers\StringGenerator;
use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * TransactionLog model
 *
 * @property integer $id;
 * @property integer $transaction_id;
 * @property string $reference_id;
 * @property integer $status;
 * @property string $created_at;
 * @property string $created_by;
 * @property string $updated_at;
 * @property string $updated_by;  
 */
class TransactionLog extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'transaction_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['reference_id'], 'string'],
            [['transaction_id', 'status'], 'integer'],
            [['created_at', 'created_by', 'updated_at', 'updated_by'], 'safe'],
        ];

        return $rules;
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->created_at = new Expression('NOW()');

                $reference_id = StringGenerator::generateReferenceId();
                // make sure it is unique
                $loop = true;
                while ($loop) {
                    $result = self::find()
                        ->where(['reference_id' => $reference_id])
                        ->one();
                    if ($result) {
                        //re-generate again
                        $reference_id = StringGenerator::generateReferenceId();
                    } else {
                        $loop = false;
                    }
                }
                $this->reference_id = $reference_id;
            } else {
                $this->updated_at = new Expression('NOW()');
            }
            return true;
        } else {
            return false;
        }
    }
}
