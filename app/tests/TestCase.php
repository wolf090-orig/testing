<?php

namespace app\tests;

use Yii;
use yii\helpers\ArrayHelper;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->mockApplication();
    }

    protected function tearDown(): void
    {
        $this->destroyApplication();
        parent::tearDown();
    }

    protected function mockApplication($config = [], $appClass = '\yii\web\Application')
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => dirname(__DIR__),
            'vendorPath' => dirname(__DIR__) . '/vendor',
            'components' => [
                'db' => [
                    'class' => 'yii\db\Connection',
                    'dsn' => 'pgsql:host=postgres;port=5432;dbname=loans',
                    'username' => 'user',
                    'password' => 'password',
                    'charset' => 'utf8',
                ],
                'request' => [
                    'cookieValidationKey' => 'test',
                    'enableCsrfValidation' => false,
                ],
            ],
            'params' => [
                'loan' => [
                    'approvalProbability' => 0.1,
                    'maxApprovedLoansPerUser' => 1,
                ],
            ],
        ], $config));
    }

    protected function destroyApplication()
    {
        Yii::$app = null;
    }
}