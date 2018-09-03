<?php
/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 25.08.15
 * Time: 20:15
 */

//В bitrix-refresh.php  хранится токен доступа, ему нужны права на запись

return [
    'dev' => [
        //идентификатор приложения
        'client_id' => 'local.55a6ca262e8482.19745138',
        //секретный код приложения
        'client_secret' => '3c04629083254f498ec482d73b14deb9',
        'scope' => 'crm,user,telephony',
        //домен третьего уровня клиентского проекта в Bitrix24
        'domain' => 'afinogen.bitrix24.ru',
        'mail-to' => [
            '1' => [
                'serzh28@mail.ru'
            ],
            '2' => [
                'serzh28@mail.ru'
            ],
            '3' => [
                'serzh28@mail.ru'
            ]
        ]
    ],
    'production' => [

    ],
];
