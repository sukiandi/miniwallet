<?php
namespace app\models;

use StringGenerator;
use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Transaction model
 * to serve 'deposit' and 'withdraw' features
 *
 * @property integer $id;
 * @property string $customer_xid;
 * @property string $type;
 * @property float $amount;
 * @property string $reference_id;
 * @property integer $status;
 * @property string $created_at;
 * @property string $created_by;
 * @property string $updated_at;
 * @property string $updated_by;  
 */
class Transaction extends ActiveRecord
{
    const TYPE_DEPOSIT = "deposit";
    const TYPE_WITHDRAW = "withdraw";

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'transaction';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['customer_xid', 'reference_id'], 'required'],
            [['customer_xid', 'reference_id', 'type'], 'string'],
            [['status'], 'integer'],
            [['created_at', 'created_by', 'updated_at', 'updated_by', 'amount'], 'safe'],
        ];

        return $rules;
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->created_at = new Expression('NOW()');
                $this->created_by = $this->customer_xid;
            } else {
                $this->updated_at = new Expression('NOW()');
                $this->updated_by = $this->customer_xid;
            }
            return true;
        } else {
            return false;
        }
    }

    public function isReferenceIdExist($reference_id, $type)
    {
        return self::findOne(['reference_id' => $reference_id, 'type' => $type]);
    }
}
