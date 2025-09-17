<?php

namespace app\models;

use yii\base\NotSupportedException;
use yii\web\IdentityInterface;

/**
 * User model - minimal implementation for Yii2 compatibility
 * Authentication is not required for this API
 */
class User implements IdentityInterface
{
    public $id;
    public $username;
    public $authKey;
    public $accessToken;

    public static function findIdentity($id)
    {
        return null;
    }

    public static function findIdentityByAccessToken($token, $type = null)
    {
        return null;
    }

    public static function findByUsername($username)
    {
        return null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthKey()
    {
        return $this->authKey;
    }

    public function validateAuthKey($authKey)
    {
        return $this->authKey === $authKey;
    }

    public function validatePassword($password)
    {
        return false;
    }
}