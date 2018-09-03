<?php
/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 08.09.15
 * Time: 11:35
 */
require __DIR__.'/../autoload.php';

$config = require __DIR__.'/../config/config.php';
$refresh = require __DIR__.'/../config/bitrix-refresh.php';
$api = new \Bitrix24\api\Bitrix24API(array_merge($config['production'], $refresh));

file_put_contents(__DIR__.'/../config/bitrix-refresh.php', "<?php return array('refresh_token' => '".$api->getRefreshToken()."', 'time' => ".time().");");