<?php

/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 13.07.15
 * Time: 9:12
 */

namespace Bitrix24\api;

/**
 * Class bitrix24Api
 * Основной класс работы с апи битрикс24
 */
class Bitrix24API
{
    /**
     * Конфигурация приложения
     * @var array
     */
    protected $_config = array();

    protected $_msg;

    /**
     * Сессионный токен
     * @var string
     */
    protected $_accessToken;

    /**
     * Токен обновления авторизации
     * @var string
     */
    protected $_refresh_token;


    /**
     * Задание параметров приложения при создании класса
     * @param array $_config
     * @throws \Exception
     */
    public function __construct($_config)
    {
        if(empty($_config) || !is_array($_config)){
            throw new \Exception('Пустой конфигурационный файл');
        }

        if(empty($_config['client_id'])
            || empty($_config['client_secret'])
            || empty($_config['refresh_token'])
            || empty($_config['domain'])
        ){
            throw new \Exception('Не корректный конфигурационный файл');
        }

        $this->_config = $_config;
    }

    /**
     * Получение сессионного токена
     * @return string
     * @throws \Exception
     */
    public function getAccessToken()
    {
        if(!$this->_accessToken){

            $getQuery = array(
                'grant_type' => 'refresh_token',
                'client_id' => $this->_config['client_id'],
                'client_secret' => $this->_config['client_secret'],
                'scope' => $this->_config['scope'],
                'refresh_token' => $this->_config['refresh_token']
            );

            $url = 'oauth/token/';
            $response = $this->sendRequest($url, $getQuery);

            if(property_exists($response, 'access_token')){
                $this->_accessToken = $response->access_token;
            }else{
                throw new \Exception('Ошибка авторизации');
            }
        }

        return $this->_accessToken;
    }

    /**
     * Получение токена для продления авторизации
     * @return string
     * @throws \Exception
     */
    public function getRefreshToken()
    {
        if(!$this->_accessToken){

            $getQuery = array(
                'grant_type' => 'refresh_token',
                'client_id' => $this->_config['client_id'],
                'client_secret' => $this->_config['client_secret'],
                'scope' => $this->_config['scope'],
                'refresh_token' => $this->_config['refresh_token']
            );

            $url = 'oauth/token/';
            $response = $this->sendRequest($url, $getQuery);

            if(property_exists($response, 'refresh_token')){
                $this->_refresh_token = $response->refresh_token;
            }else{
                throw new \Exception('Ошибка авторизации');
            }
        }

        return $this->_refresh_token;
    }

    /**
     * Отправка запросов на сервер
     * @param $_url
     * @return mixed
     */
    protected function sendRequest($_url, $_data)
    {
        $_url = 'https://'.$this->_config['domain'].'/'.$_url.'?'.http_build_query($_data);

        $ch = curl_init($_url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);

        return json_decode($res);
    }

    /**
     * Сообщение об ошибке
     * @return string
     */
    public function getMsg()
    {
        return trim(strip_tags($this->_msg));
    }
}