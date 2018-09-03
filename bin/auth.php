<?php
/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 25.08.15
 * Time: 20:32
 */

$config = require __DIR__.'/../config/config.php';
$config = $config['production'];

$_url = 'https://'.$config['domain'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $_url);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
$res = curl_exec($ch);

$l = '';
if(preg_match('#Location: (.*)#', $res, $r)) {
    $l = trim($r[1]);
}
//echo $l.PHP_EOL;

curl_setopt($ch, CURLOPT_URL, $l);
$res = curl_exec($ch);

preg_match('#name="backurl" value="(.*)"#', $res, $math);

$post = http_build_query([
    'AUTH_FORM' => 'Y',
    'TYPE' => 'AUTH',
    'backurl' => $math[1],
    'USER_LOGIN' => $config['login'],
    'USER_PASSWORD' => $config['password'],
    'USER_REMEMBER' => 'Y'
]);


curl_setopt($ch, CURLOPT_URL, 'https://www.bitrix24.net/auth/');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
$res = curl_exec($ch);

$l = '';
if(preg_match('#Location: (.*)#', $res, $r)) {
    $l = trim($r[1]);
}
//echo $l.PHP_EOL;

curl_setopt($ch, CURLOPT_URL, $l);
$res = curl_exec($ch);

$l = '';
if(preg_match('#Location: (.*)#', $res, $r)) {
    $l = trim($r[1]);
}
//echo $l.PHP_EOL;

curl_setopt($ch, CURLOPT_URL, $l);
$res = curl_exec($ch);

//end autorize

curl_setopt($ch, CURLOPT_URL, 'https://'.$config['domain'].'/oauth/authorize/?response_type=code&client_id='.$config['client_id']);
$res = curl_exec($ch);

$l = '';
if(preg_match('#Location: (.*)#', $res, $r)) {
    $l = trim($r[1]);
}

preg_match('/code=(.*)&do/', $l, $code);
$code = $code[1];

curl_setopt($ch, CURLOPT_URL, 'https://'.$config['domain'].'/oauth/token/?grant_type=authorization_code&client_id='.$config['client_id'].'&client_secret='.$config['client_secret'].'&code='.$code.'&scope=crm,user,telephony');
curl_setopt($ch, CURLOPT_HEADER, false);
$res = curl_exec($ch);

curl_close($ch);


echo $res;
