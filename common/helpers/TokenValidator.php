<?php

namespace app\common\helpers;

use app\models\AccessToken;
use Yii;

/**
 * TokenValidator
 */
class TokenValidator
{
    public static function validateToken($token) {
        $token = str_replace("Bearer ", "", $token);
        $token = str_replace("Token ", "", $token);
        $token_query = AccessToken::find()
            ->where([
                'status' => 1
            ])
            ->orderBy(['created_at' => SORT_DESC])
            ->one();
        
        if ($token_query && $token_query->token == $token) {
            return $token_query;
        }

        return false;
    }
}