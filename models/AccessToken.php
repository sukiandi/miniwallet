<?php
namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\db\Expression;

/**
 * Access Token model
 * 
 * @property integer $id;
 * @property string $token;
 * @property string $customer_xid;
 * @property integer $status;
 * @property string $created_at;
 * @property string $created_by;
 * @property string $updated_at;
 * @property string $updated_by;
 */
class AccessToken extends ActiveRecord
{
    const STATUS_SUCCESS = "success";
    const STATUS_FAILED = "failed";
    const ERR_MSG_INVALID_TOKEN = "Your token is invalid or not the latest. Please re-generate and submit again with the latest.";

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'access_token';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = [
            [['token', 'customer_xid'], 'required'],
            [['token', 'customer_xid'], 'string'],
            [['status'], 'integer'],
            [['created_at', 'created_by', 'updated_at', 'updated_by'], 'safe'],
        ];

        return $rules;
    }

    public function beforeSave($insert) {
        if (parent::beforeSave($insert)) {
            if ($this->isNewRecord) {
                $this->created_at = new Expression('NOW()');
                $this->created_by = $this->customer_xid;
                $this->status = 1;
            } else {
                $this->updated_at = new Expression('NOW()');
                $this->updated_by = $this->customer_xid;
            }
            return true;
        } else {
            return false;
        }
    }
}
