<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Wallet model
 *
 * @property integer $id;
 * @property string $customer_xid;
 * @property float $balance;
 * @property integer $status;
 * @property string $enabled_at;
 * @property string $created_at;
 * @property string $created_by;
 * @property string $updated_at;
 * @property string $updated_by; 
 */
class Wallet extends ActiveRecord
{
    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;
    const ERR_MSG_DISABLED = "Your wallet is disabled, Please enable your wallet in order to use.";
    const ERR_MSG_ENABLED = "Your wallet is already enabled.";
    const ERR_MSG_NOT_EXIST = "Your wallet is not exist.";

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wallet';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['customer_xid'], 'required'],
            [['customer_xid'], 'string'],
            [['status'], 'integer'],
            [['enabled_at', 'disabled_at', 'created_at', 'created_by', 'updated_at', 'updated_by', 'balance'], 'safe'],
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

    public static function getByCustomerId($customer_xid)
    {
        return self::findOne(['customer_xid' => $customer_xid]);
    }
}
