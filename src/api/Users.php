<?php
/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 28.08.15
 * Time: 10:46
 */

namespace Bitrix24\api;


class Users extends Bitrix24API
{
    public function getUser($_id)
    {
        $url = 'user.get';
        $data = [
            'ID' => $_id
        ];

        return $this->sendRequest($url, $data);
    }
}