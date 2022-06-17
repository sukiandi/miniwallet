<?php
namespace app\common\models;

use app\models\AccessToken;
use Yii;
use yii\base\Model;

/**
 * Response model
 *
 * @property string $status
 * @property mixed $data
 */
class Response extends Model
{
    public $status;
    public $data;
    public $error; 

    public function __construct($status = AccessToken::STATUS_SUCCESS, $data = [], $error = []) {
        $this->status = $status;
        $this->data = $data;
        $this->error = $error;
    }
}
