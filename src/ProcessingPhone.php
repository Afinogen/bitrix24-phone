<?php
/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 27.08.15
 * Time: 19:56
 */

namespace Bitrix24;


use Bitrix24\api\Bitrix24Telephony;

/**
 * Class ProcessingPhone
 * @package Bitrix24
 */
class ProcessingPhone extends Bitrix24Telephony
{
    const HOUR = 1;
    const DAY = 2;
    const WEEK = 3;

    const CALL_TYPE_OK = 200;
    const CALL_TYPE_SKIPPED = 304;

    protected $_users = [];

    /**
     * Список входящих звонков
     * @param int $_typeTime
     * @return array
     */
    public function getListInCall($_typeTime)
    {
        $filter = [
            //'CALL_FAILED_CODE' => 304,
            'CALL_TYPE' => [
                2,
                3
            ],
            '>CALL_START_DATE' => $this->getDateTime($_typeTime)
        ];

        return $this->_getCalls($filter, $_typeTime);
    }

    /**
     * Получение списка звонков по фильтру
     * @param array $_filter
     * @param int $_typeTime
     * @return array
     */
    protected function _getCalls($_filter, $_typeTime)
    {
        $date = $this->getDateTime($_typeTime);

        $res = $this->getStatistic($_filter);

        $calls = [];
        $exit = false;

        do{
            $items = $res->result;
            foreach($items as $item){
                if(strtotime($item->CALL_START_DATE) < $date){
                    $exit = true;
                    break;
                }
                $calls[] = $item;
            }
            if(!$exit){
                $res = $this->getStatistic($_filter, $res->next);
            }
            if($_typeTime == self::HOUR){
                $exit = true;
            }
        }while(!$exit);

        return $calls;
    }

    /**
     * Полученине исходящих звонков
     * @param int $_typeTime
     * @return array
     */
    public function getListOutCall($_typeTime)
    {
        $filter = [
            'CALL_TYPE' => 1,
            '>CALL_START_DATE' => $this->getDateTime($_typeTime)
        ];

        return $this->_getCalls($filter, $_typeTime);
    }

    /**
     * Обработка звонков
     * @param int $_typeTime
     * @return array|bool
     */
    public function getProcessingCall($_typeTime)
    {
        $inputCalls = $this->getListInCall($_typeTime);
        $outCalls = $this->getListOutCall($_typeTime);

        $processedCalls = [];
        $notProcessedCalls = [];

        $ids = [];

        $uniqPhone = [];

        $reCall = 0;

        foreach($inputCalls as $inputCall){
            if ($inputCall->CALL_FAILED_CODE == self::CALL_TYPE_SKIPPED) {
                $employeeFioIn = $inputCall->PORTAL_USER_ID ? $this->getUsers($inputCall->PORTAL_USER_ID) : '';
                foreach ($outCalls as $outCall) {
                    if ($outCall->PHONE_NUMBER == $inputCall->PHONE_NUMBER && strtotime($outCall->CALL_START_DATE) > strtotime($inputCall->CALL_START_DATE)) {
                        $okCall = $this->getCall200($inputCall, $inputCalls);
                        //клиент сам перезвонил
                        if ($okCall && strtotime($okCall->CALL_START_DATE) < strtotime($outCall->CALL_START_DATE)) {
                            $reCall++;
                            break;
                        }
                        //перезвонил сотрудник
                        $employeeFioOut = $outCall->PORTAL_USER_ID ? $this->getUsers($outCall->PORTAL_USER_ID) : '';
                        $processedCalls[$inputCall->ID] = [
                            '{phone}' => $inputCall->PHONE_NUMBER,
                            '{date_skipped}' => date('d.m.Y H:i', strtotime($inputCall->CALL_START_DATE)),
                            '{employee}' => $employeeFioIn,
                            '{date_out_call}' => date('d.m.Y H:i', strtotime($outCall->CALL_START_DATE)),
                            '{employee_out}' => $employeeFioOut
                        ];
                        $ids['out'][$outCall->ID] = $outCall->ID;
                        break;
                    }
                }
                //забили на звонок
                if (!isset($processedCalls[$inputCall->ID])) {
                    $notProcessedCalls[] = [
                        '{phone}' => $inputCall->PHONE_NUMBER,
                        '{date_skipped}' => date('d.m.Y H:i', strtotime($inputCall->CALL_START_DATE)),
                        '{employee}' => $employeeFioIn,
                    ];
                    $ids['in'][$inputCall->ID] = $inputCall->ID;
                }
            }
            if (!isset($uniqPhone[$inputCall->PHONE_NUMBER])) {
                $uniqPhone[$inputCall->PHONE_NUMBER] = 1;
            }
        }

        $oldIds = json_decode(file_get_contents(__DIR__.'/../data/old.ids'), true);
        $notIsset = true;
        if ($oldIds) {
            $notIsset = false;
            if (isset($ids['in'])){
                foreach($ids['in'] as $i){
                    if(!isset($oldIds['in'][$i])){
                        $notIsset = true;
                    }
                }
            }
            if (isset($ids['out'])){
                foreach($ids['out'] as $i){
                    if(!isset($oldIds['out'][$i])){
                        $notIsset = true;
                    }
                }
            }
        }

        if (empty($oldIds) && empty($processedCalls) && empty($notProcessedCalls)) {
            $notIsset = false;
        }

        file_put_contents(__DIR__.'/../data/old.ids', json_encode($ids));

        if ($notIsset || $_typeTime != self::HOUR){
            $stat = [];
            if ($_typeTime == self::DAY || $_typeTime == self::WEEK) {
                $stat = [
                    '{quantity-all}' => count($uniqPhone),
                    '{quantity-skipped-not-processed}' => 0,
                    '{quantity-skipped-processed}' => 0,
                    '{quantity-recall}' => $reCall,
                    '{percent-recall}' => round($reCall / count($uniqPhone) * 100,2)
                ];
                foreach($notProcessedCalls as $skipped) {
                    if (isset($uniqPhone[$skipped['{phone}']])) {
                        $stat['{quantity-skipped-not-processed}']++;
                    }
                }

                foreach($processedCalls as $skipped) {
                    if (isset($uniqPhone[$skipped['{phone}']])) {
                        $stat['{quantity-skipped-processed}']++;
                    }
                }
                $stat['{quantity-skipped}'] = $stat['{quantity-skipped-not-processed}'] + $stat['{quantity-skipped-processed}'];
                $stat['{percent-skipped}'] = round($stat['{quantity-skipped}'] / $stat['{quantity-all}'] * 100, 2);
                $stat['{percent-skipped-not-processed}'] = round($stat['{quantity-skipped-not-processed}'] / $stat['{quantity-skipped}'] * 100, 2);
                $stat['{percent-skipped-processed}'] = round($stat['{quantity-skipped-processed}'] / $stat['{quantity-skipped}'] * 100, 2);
                $stat['{percent-not-processed-skipped}'] = round($stat['{quantity-skipped-not-processed}'] / $stat['{quantity-all}'] * 100, 2);
            }
            return [
                $processedCalls,
                $notProcessedCalls,
                $stat
            ];
        }

        return false;
    }

    /**
     * Получение ФИО пользователя
     * @param int $_id
     * @return string
     * @throws \Exception
     */
    public function getUsers($_id)
    {
        if(isset($this->_users[$_id])){
            return $this->_users[$_id];
        }

        $url = 'rest/user.get';
        $data = [
            'auth' => $this->getAccessToken(),
            'ID' => $_id
        ];
        $user = $this->sendRequest($url, $data)->result[0];
        $this->_users[$_id] = $user->NAME.' '.$user->SECOND_NAME.' '.$user->LAST_NAME;

        return $this->_users[$_id];
    }

    /**
     * Отправка отчета
     * @param array $calls
     * @param $_typeTime
     */
    public function sendMail($calls, $_typeTime)
    {
        $main = file_get_contents(__DIR__.'/template/main.html');
        $inItem = file_get_contents(__DIR__.'/template/input-call.html');
        $outItem = file_get_contents(__DIR__.'/template/out-call.html');
        $table = '';

        foreach($calls[1] as $item) {
            $table .= str_replace(array_keys($item), array_values($item), $inItem);
        }
        $main = str_replace('{items-in}', $table, $main);
        $table = '';

        foreach($calls[0] as $item) {
            $table .= str_replace(array_keys($item), array_values($item), $outItem);
        }
        $main = str_replace('{items-out}', $table, $main);

        $dateEnd = date('d.m.Y H:i');
        $dateStart = date('d.m.Y H:i', $this->getDateTime($_typeTime));

        $stat = '';
        if (($_typeTime == self::DAY || $_typeTime == self::WEEK) && !empty($calls[2])){
            $stat = file_get_contents(__DIR__.'/template/stat.html');
            $calls[2]['{date-start}'] = $dateStart;
            $calls[2]['{date-end}'] = $dateEnd;
            $stat = str_replace(array_keys($calls[2]), array_values($calls[2]), $stat);
        }

        $data = [
            '{date-start}' => $dateStart,
            '{date-end}' => $dateEnd,
            '{total-in}' => count($calls[1]),
            '{total-out}' => count($calls[0]),
            '{stat}' => $stat
        ];

        $subjects = [
            self::HOUR => 'Необработанные пропущенные',
            self::DAY => 'Отчет по пропущенным звонкам за '.$dateEnd,
            self::WEEK => 'Отчет по пропущенным звонкам за неделю с '.$dateStart.' по '.$dateEnd
        ];

        $main = str_replace(array_keys($data), array_values($data), $main);

        $headers  = 'MIME-Version: 1.0' . "\r\n";
        $headers .= 'Content-type: text/html; charset=utf-8' . "\r\n";
        $headers .= 'From: OZ <sync@commond.ru>' . "\r\n";

        foreach($this->_config['mail-to'][$_typeTime] as $email){
            mail($email, $subjects[$_typeTime], $main, $headers);
        }
    }

    /**
     * Вычисление даты
     * @param int $_type
     * @return int|string
     */
    public function getDateTime($_type)
    {
        $date = '';

        if ($_type == self::HOUR) {
            $m = date('m');
            $d = (int)date('d');
            $y = date('Y');

            $d--;
            $date = mktime(17, 0, 0, $m, $d, $y);
        } else if($_type == self::DAY){
            $m = date('m');
            $d = (int)date('d');
            $y = date('Y');

            $d--;
            $date = mktime(18, 0, 0, $m, $d, $y);
        }else if ($_type == self::WEEK) {
            $m = date('m');
            $d = (int)date('d');
            $y = date('Y');

            $d-=7;
            $date = mktime(17, 0, 0, $m, $d, $y);
        }

        return $date;
    }

    /**
     * Получение удачного входящего звонка
     * @param object $_inCall
     * @param object[] $_calls
     * @return bool|object
     */
    public function getCall200($_inCall, $_calls)
    {
        foreach($_calls as $call) {
            if (
                $call->PHONE_NUMBER == $_inCall->PHONE_NUMBER &&
                strtotime($call->CALL_START_DATE) > strtotime($_inCall->CALL_START_DATE) &&
                $call->CALL_FAILED_CODE == self::CALL_TYPE_OK
            ) {
                return $call;
            }
        }

        return false;
    }
}