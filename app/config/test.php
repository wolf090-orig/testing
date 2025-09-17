<?php

return [
    'id' => 'basic-tests',
    'basePath' => dirname(__DIR__),
    'language' => 'ru-RU',
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'pgsql:host=postgres;port=5432;dbname=loans',
            'username' => 'user',
            'password' => 'password',
            'charset' => 'utf8',
        ],
        'mailer' => [
            'class' => 'yii\symfonymailer\Mailer',
            'viewPath' => '@app/mail',
            'useFileTransport' => true,
        ],
        'assetManager' => [
            'basePath' => __DIR__ . '/../web/assets',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                'POST requests' => 'requests/create',
                'GET processor' => 'processor/process',
            ],
        ],
        'user' => [
            'identityClass' => 'app\models\User',
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
];