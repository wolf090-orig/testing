<?php

return [
    'class' => 'yii\db\Connection',
    'dsn' => 'pgsql:host=' . (getenv('DB_HOST') ?: 'localhost') . ';port=' . (getenv('DB_PORT') ?: '5432') . ';dbname=' . (getenv('DB_NAME') ?: 'loans'),
    'username' => getenv('DB_USER') ?: 'user',
    'password' => getenv('DB_PASSWORD') ?: 'password',
    'charset' => 'utf8',

    // Schema cache options (for production environment)
    'enableSchemaCache' => true,
    'schemaCacheDuration' => 60,
    'schemaCache' => 'cache',
];