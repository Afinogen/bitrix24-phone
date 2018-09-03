<?php
/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 25.08.15
 * Time: 20:24
 */
require __DIR__.'/../autoload.php';

$config = require __DIR__.'/../config/config.php';
$refresh = require __DIR__.'/../config/bitrix-refresh.php';
$api = new \Bitrix24\ProcessingPhone(array_merge($config['production'], $refresh));

$api->sendMail($api->getProcessingCall(\Bitrix24\ProcessingPhone::DAY), \Bitrix24\ProcessingPhone::DAY);
