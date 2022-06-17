<?php

namespace app\modules\api\controllers;

use app\common\helpers\TokenValidator;
use app\common\models\Response;
use app\models\AccessToken;
use app\models\Transaction;
use app\models\TransactionLog;
use app\models\Wallet;
use Yii;
use yii\db\Expression;
use yii\rest\ActiveController;
use yii\web\MethodNotAllowedHttpException;

/**
 * V1 controller for the `api` module
 */
class V1Controller extends ActiveController
{
    public $modelClass = 'app\common\models\Response';

    public function actionInit()
    {
        $response = new Response();
        $post = Yii::$app->request->post();

        // Reject request other than POST method
        if (!Yii::$app->request->isPost) {
            throw new MethodNotAllowedHttpException('You are not allowed to perform this method.');
        }

        if (!isset($post['customer_xid'])) {
            $response->status = AccessToken::STATUS_FAILED;
            $response->error = ["message" => "Missing parameter 'customer_xid'."];
            return $response;
        };

        $customer_xid = $post['customer_xid'];

        $token = new AccessToken();
        $token->customer_xid = $customer_xid;
        $token->token = Yii::$app->security->generateRandomString();

        if ($token->save()) {
            $response->status = AccessToken::STATUS_SUCCESS;
            $response->data = [
                "token" => $token->token
            ];

            // check if already exist
            $wallet_exist = Wallet::findOne(['customer_xid' => $customer_xid]);
            if ($wallet_exist) {
                $wallet = $wallet_exist;
            } else {
                // create new wallet
                $wallet = new Wallet();
                $wallet->customer_xid = $customer_xid;
                $wallet->status = Wallet::STATUS_DISABLED; // disabled by default
                if (!$wallet->save()) {
                    $response->status = AccessToken::STATUS_FAILED;
                    $response->error = $wallet->getErrors();
                }
            }

        } else {
            $response->status = AccessToken::STATUS_FAILED;
            $response->error = $token->getErrors();
        }

        return $response;
    }

    public function actionWallet()
    {
        $wallet = null;
        $response = new Response(AccessToken::STATUS_FAILED);

        $bearer_token = Yii::$app->request->headers->get('authorization');

        if ($token = TokenValidator::validateToken($bearer_token)) {
            $wallet = Wallet::getByCustomerId($token->customer_xid);
            if (!$wallet) {
                $response->error = ["message" => Wallet::ERR_MSG_NOT_EXIST];
                return $response;
            }
        } else {
            $response->error = ["message" => AccessToken::ERR_MSG_INVALID_TOKEN];
            return $response;
        }

        if (Yii::$app->request->isPost) { //enable wallet
            $response = $this->_enableWallet($wallet);
        } else if (Yii::$app->request->isGet) { // get wallet
            $response = $this->_getWallet($wallet);
        } else if (Yii::$app->request->isPatch) { // disable wallet
            $response = $this->_disableWallet($wallet, Yii::$app->request->post());
        }

        return $response;
    }

    public function actionDeposits()
    {
        $wallet = null;
        $response = new Response(AccessToken::STATUS_FAILED);

        if (!Yii::$app->request->isPost) {
            throw new MethodNotAllowedHttpException('You are not allowed to perform this method.');
        }
        $bearer_token = Yii::$app->request->headers->get('authorization');
        $post = Yii::$app->request->post();

        if ($token = TokenValidator::validateToken($bearer_token)) {
            $wallet = Wallet::getByCustomerId($token->customer_xid);
            if (!$wallet) {
                $response->error = ["message" => Wallet::ERR_MSG_NOT_EXIST];
                return $response;
            }
        } else {
            $response->error = ["message" => AccessToken::ERR_MSG_INVALID_TOKEN];
            return $response;
        }

        if (!isset($post['amount'])) {
            $response->error = ["message" => "Missing parameter 'amount'."];
            return $response;
        }
        if (!is_numeric($post['amount'])) {
            $response->error = ["message" => "Invalid 'amount' value."];
            return $response;
        }
        if (!isset($post['reference_id'])) {
            $response->error = ["message" => "Missing parameter 'reference_id'."];
            return $response;
        }

        if ($wallet->status == Wallet::STATUS_DISABLED) {
            $response->error = ["message" => Wallet::ERR_MSG_DISABLED];
            return $response;
        }

        // Check if reference_id is unique
        $transaction_query = Transaction::find()
            ->where([
                'customer_xid' => $wallet->customer_xid,
                'reference_id' => $post['reference_id'],
                'type' => Transaction::TYPE_DEPOSIT,
                'status' => 1
            ])
            ->one();
        
        if ($transaction_query) {
            $response->error = ["message" => "Reference ID is already in used."];
            return $response;
        }

        $transaction = new Transaction();
        $transaction->customer_xid = $wallet->customer_xid;
        $transaction->type = Transaction::TYPE_DEPOSIT;
        $transaction->amount = $post['amount'];
        $transaction->reference_id = $post['reference_id'];
        
        if ($transaction->save()) {
            $transaction->refresh();
            $transactionLog = new TransactionLog();
            $transactionLog->transaction_id = $transaction->id;
            if ($transactionLog->save()) {
                $transactionLog->refresh();
                //update wallet balance
                $wallet->balance += $transaction->amount;
                $wallet->save();
                $response->status = AccessToken::STATUS_SUCCESS;
                $response->data = [
                    "deposit" => [
                        "id" => $transaction->id,
                        "deposited_by" => $transaction->created_by,
                        "status" => AccessToken::STATUS_SUCCESS,
                        "deposited_at" => $transaction->created_at,
                        "amount" => $transaction->amount,
                        "reference_id" => $transactionLog->reference_id
                    ]
                ];
            } else {
                $response->error = $transactionLog->getErrors();
            }
        } else {
            $response->error = $transaction->getErrors();
        }

        return $response;
    }

    public function actionWithdrawals()
    {
        $wallet = null;
        $response = new Response(AccessToken::STATUS_FAILED);

        if (!Yii::$app->request->isPost) {
            throw new MethodNotAllowedHttpException('You are not allowed to perform this method.');
        }
        $bearer_token = Yii::$app->request->headers->get('authorization');
        $post = Yii::$app->request->post();

        if ($token = TokenValidator::validateToken($bearer_token)) {
            $wallet = Wallet::getByCustomerId($token->customer_xid);
            if (!$wallet) {
                $response->error = ["message" => Wallet::ERR_MSG_NOT_EXIST];
                return $response;
            }
        } else {
            $response->error = ["message" => AccessToken::ERR_MSG_INVALID_TOKEN];
            return $response;
        }

        if (!isset($post['amount'])) {
            $response->error = ["message" => "Missing parameter 'amount'."];
            return $response;
        }
        if (!is_numeric($post['amount'])) {
            $response->error = ["message" => "Invalid 'amount' value."];
            return $response;
        }
        if (!isset($post['reference_id'])) {
            $response->error = ["message" => "Missing parameter 'reference_id'."];
            return $response;
        }

        if ($wallet->status == Wallet::STATUS_DISABLED) {
            $response->error = ["message" => Wallet::ERR_MSG_DISABLED];
            return $response;
        }

        // Check if reference_id is unique
        $transaction_query = Transaction::find()
            ->where([
                'customer_xid' => $wallet->customer_xid,
                'reference_id' => $post['reference_id'],
                'type' => Transaction::TYPE_WITHDRAW,
                'status' => 1
            ])
            ->one();
        
        if ($transaction_query) {
            $response->error = ["message" => "Reference ID is already in used."];
            return $response;
        }

        if ($wallet->balance < (float) $post['amount']) {
            $response->error = ["message" => "Insufficient balance."];
            return $response;
        }

        $transaction = new Transaction();
        $transaction->customer_xid = $wallet->customer_xid;
        $transaction->type = Transaction::TYPE_WITHDRAW;
        $transaction->amount = $post['amount'];
        $transaction->reference_id = $post['reference_id'];
        
        if ($transaction->save()) {
            $transaction->refresh();
            $transactionLog = new TransactionLog();
            $transactionLog->transaction_id = $transaction->id;
            if ($transactionLog->save()) {
                $transactionLog->refresh();
                //update wallet balance
                $wallet->balance -= $transaction->amount;
                $wallet->save();
                $response->status = AccessToken::STATUS_SUCCESS;
                $response->data = [
                    "withdrawal" => [
                        "id" => $transaction->id,
                        "withdrawn_by" => $transaction->created_by,
                        "status" => AccessToken::STATUS_SUCCESS,
                        "withdrawn_at" => $transaction->created_at,
                        "amount" => $transaction->amount,
                        "reference_id" => $transactionLog->reference_id
                    ]
                ];
            } else {
                $response->error = $transactionLog->getErrors();
            }
        } else {
            $response->error = $transaction->getErrors();
        }

        return $response;
    }

    private function _enableWallet($wallet)
    {
        $response = new Response(AccessToken::STATUS_FAILED);

        if ($wallet->status == Wallet::STATUS_DISABLED) {
            $wallet->status = Wallet::STATUS_ENABLED;
            $wallet->enabled_at = new Expression('NOW()');
            $wallet->save();
            $wallet->refresh();

            $response->status = AccessToken::STATUS_SUCCESS;
            $response->data = [
                "wallet" => [
                    "id" => $wallet->id,
                    "owned_by" => $wallet->customer_xid,
                    "status" => $wallet->status == Wallet::STATUS_ENABLED ? "enabled" : "disabled",
                    "enabled_at" => $wallet->enabled_at,
                    "balance" => $wallet->balance
                ]
            ];
        } else {
            $response->error = ["message" => Wallet::ERR_MSG_ENABLED];
        }

        return $response;
    }

    private function _getWallet($wallet)
    {
        $response = new Response(AccessToken::STATUS_FAILED);

        if ($wallet->status == Wallet::STATUS_DISABLED) {
            $response->error = ["message" => Wallet::ERR_MSG_DISABLED];
        } else {
            $response->status = AccessToken::STATUS_SUCCESS;
            $response->data = [
                "wallet" => [
                    "id" => $wallet->id,
                    "owned_by" => $wallet->customer_xid,
                    "status" => $wallet->status == Wallet::STATUS_ENABLED ? "enabled" : "disabled",
                    "enabled_at" => $wallet->enabled_at,
                    "balance" => $wallet->balance
                ]
            ];
        }

        return $response;
    }

    private function _disableWallet($wallet, $post)
    {
        $response = new Response(AccessToken::STATUS_FAILED);

        if (!isset($post['is_disabled'])) {
            $response->error = ["message" => "Missing parameter 'is_disabled'."];
            return $response;
        }

        if ($wallet->status == Wallet::STATUS_DISABLED) {
            $response->error = ["message" => Wallet::ERR_MSG_DISABLED];
        } else {
            if ($post['is_disabled'] == 'true') {
                $wallet->status = Wallet::STATUS_DISABLED;
                $wallet->disabled_at = new Expression('NOW()');
                $wallet->save();
                $wallet->refresh();
            }
            $response->status = AccessToken::STATUS_SUCCESS;
            $response->data = [
                "wallet" => [
                    "id" => $wallet->id,
                    "owned_by" => $wallet->customer_xid,
                    "status" => $wallet->status == Wallet::STATUS_ENABLED ? "enabled" : "disabled",
                    "disabled_at" => $wallet->disabled_at,
                    "balance" => $wallet->balance
                ]
            ];
        }

        return $response;
    }
}
