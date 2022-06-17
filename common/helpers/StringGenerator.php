<?php

namespace app\common\helpers;

/**
 * Helper Class : StringGenerator
 */
class StringGenerator
{
    public static function generateRandomString($length = 1) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function generateReferenceId() {
        $reference_id = self::generateRandomString(8) . '-' .
                        self::generateRandomString(4) . '-' .  
                        self::generateRandomString(4) . '-' .  
                        self::generateRandomString(4) . '-' .  
                        self::generateRandomString(16); 
        return $reference_id;
    }
}