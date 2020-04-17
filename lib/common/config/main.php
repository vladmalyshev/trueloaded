<?php

return [
    'timeZone' => date_default_timezone_get(),
    'vendorPath' => dirname(dirname(__DIR__)) . '/vendor',
    'bootstrap' => require __DIR__ . '/load-bootstrap-classes.php',
    'components' => [
      'cache' => [
          'class' => 'yii\caching\FileCache',
          'cachePath' => '@frontend/runtime/cache'
          /*
          'class' => 'yii\redis\Cache',
          'keyPrefix' => '<YOURSITE CODE>_', // change prefix!!! a unique key prefix required
          'redis' => [
              'hostname' => 'localhost',
              'port' => 6379,
              'database' => 0,
          ]
          */
      ],
      'db' => [
        'class' => 'yii\db\Connection',
        'dsn' => 'mysql:host='.DB_SERVER.';dbname='.DB_DATABASE,
        'username' => DB_SERVER_USERNAME,
        'password' => DB_SERVER_PASSWORD,
        'charset' => 'utf8',
        'enableSchemaCache' => true,
        'schemaCache' => 'cache',
        'on afterOpen' => function($event) {
          $event->sender->createCommand("SET SESSION time_zone = '".date('P')."'")->execute();
        },
      ],
        /*'cache' => [
            'class' => 'yii\caching\FileCache',
        ],*/
        /*'cache' => [
            'class' => 'yii\caching\MemCache',
            'servers' => [
                [
                    'host' => '127.0.0.1',
                    'port' => 11211,
                    'weight' => 60,
                ],
            ],
        ],*/
        /*'redis' => [
            'class' => 'yii\redis\Connection',
            'hostname' => 'localhost',
            'port' => 6379,
            'database' => 0,
        ],*/
        'platform' => [
            'class' => 'common\classes\platform',
        ],

        /*'authManager' => [
            'class' => 'yii\rbac\DbManager',
            'defaultRoles' => ['user'],
        ],*/
        'authClientCollection' => [
            'class' => 'yii\authclient\Collection',
            // all Auth clients will use this configuration for HTTP client:
            /*'httpClient' => [
                'transport' => 'yii\httpclient\CurlTransport',
            ],*/
            'clients' => [],
        ],
        'PropsHelper'=>[
            'class' => '\common\helpers\Props'
        ],
        'log' => [
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'except' => ['yii\web\HttpException:404', 'sql_error'],
                ],
                [
                    'categories' => ['sql_error'],
                    'logFile' => '@app/runtime/logs/sql_error.log',
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'logVars' => ['_SERVER'],
                ],
                [
                    'categories' => ['datasource'],
                    'logFile' => '@app/runtime/logs/datasource.log',
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning', 'info'],
                    'logVars' => [],
                ],
                [
                    'categories' => ['yii\web\HttpException:404'],
                    'logFile' => '@app/runtime/logs/404_error.log',
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                    'logVars' => ['_GET'],
                ],
            ],
        ],
      'mediaManager' => [
          'class' => 'common\classes\MediaManager',
      ],
      'settings' => [
          'class' => 'common\components\Settings', 
          'sessionKey' => 'primary-settings',
      ],
    ],
    'modules' => [
        
    ]
];
