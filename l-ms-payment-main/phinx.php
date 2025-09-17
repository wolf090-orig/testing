<?php

return
    [
        'paths' => [
            'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
            'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds'
        ],
        'environments' => [
            'default_migration_table' => 'phinxlog',
            'default_environment' => 'development',
            'production' => [
                "adapter" => "pgsql",
                "host" => "localhost", // docker env
                "name" => "webman",
                "user" => "postgres",
                "pass" => "password",
                "port" => "5436",
                "charset" => "utf8"
            ],
            'development' => [
                "adapter" => "pgsql",
                'host' => getenv('DB_HOST') ?? 'db',
                'port' => getenv('DB_PORT') ?? 5432,
                'name' => getenv('DB_DATABASE'),
                'user' => getenv('DB_USERNAME'),
                'pass' => getenv('DB_PASSWORD'),
                "charset" => "utf8"
            ],
            'testing' => [
                "adapter" => "pgsql",
                "host" => "nlu-postgres", // local env
                "name" => "ms_payment",
                "user" => "postgres",
                "pass" => "password",
                "port" => "5432",
                "charset" => "utf8"
            ]
        ],
        'version_order' => 'creation'
    ];
