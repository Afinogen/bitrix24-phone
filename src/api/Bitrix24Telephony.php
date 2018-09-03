<?php
/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 25.08.15
 * Time: 23:12
 */

namespace Bitrix24\api;

/**
 * Class Bitrix24Telephony
 * @package Bitrix24\api
 */
class Bitrix24Telephony extends Bitrix24API
{
    public function getStatistic($_fields, $_start = 0)
    {
        $url = 'rest/voximplant.statistic.get';

        $data = array(
            'auth' => $this->getAccessToken(),
            'FILTER' => $_fields,
            'SORT' => 'CALL_START_DATE',
            'ORDER' => 'DESC',
            'start' => $_start
        );

        $res = $this->sendRequest($url, $data);
        return $res;
    }
}