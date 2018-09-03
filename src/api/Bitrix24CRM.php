<?php
/**
 * Created by PhpStorm.
 * User: afinogen
 * Date: 13.07.15
 * Time: 10:15
 */

namespace Bitrix24\api;

/**
 * Class bitrix24CRM
 * Работа с ЦРМ
 */
class Bitrix24CRM extends Bitrix24API
{
    /**
     * Типы
     */
    const CRM_DEAL_STAGE = 'DEAL_STAGE';
    const CRM_DEAL_TYPE = 'DEAL_TYPE';
    const CRM_SOURCE = 'SOURCE';
    const CRM_CONTACT_TYPE = 'CONTACT_TYPE';

    /**
     * Тип значения справочника
     * @var array
     */
    private static $_entityType = array(
        self::CRM_DEAL_STAGE,   //стадия сделки
        self::CRM_DEAL_TYPE,    //тип сделки
        self::CRM_SOURCE,       //источник
        self::CRM_CONTACT_TYPE,  //тип клиентов
    );

    private $_statusList;
    private $_dealFieldsList;
    private $_contactFieldsList;


    /**
     * Значения справочников
     * @return array|string
     * @throws \Exception
     */
    public function getCrmStatusList($_type = null)
    {
        if(is_null($this->_statusList)){

            $url = 'rest/crm.status.list';
            $data = array(
                'auth' => $this->getAccessToken()
            );
            $res = $this->sendRequest($url, $data);

            if(property_exists($res, 'result')){
                foreach($res->result as $item){
                    if($item && in_array($item->ENTITY_ID, self::$_entityType)){
                        $this->_statusList[$item->ENTITY_ID][$item->NAME] = $item->STATUS_ID;
                    }
                }
            }else{
                throw new \Exception('Ошибка получения данных');
            }
        }

        return $_type ? $this->_statusList[$_type] : $this->_statusList;
    }

    /**
     * Получение ID источника
     * @param string $_name
     * @return bool|int
     * @throws \Exception
     */
    public function getSourceId($_name)
    {
        if($this->getCrmStatusList()){
            $sourceList = $this->getCrmStatusList(self::CRM_SOURCE);
            return $sourceList[$_name];
        }

        return false;
    }

    /**
     * Получение ID типа контакта
     * @param string $_name
     * @return bool|int
     * @throws \Exception
     */
    public function getTypeId($_name)
    {
        if($this->getCrmStatusList()){
            $sourceList = $this->getCrmStatusList(self::CRM_CONTACT_TYPE);
            return $sourceList[$_name];
        }

        return false;
    }

    /**
     * Тип сделки
     * @param $_name
     * @return bool|int
     * @throws \Exception
     */
    public function getDealTypeId($_name)
    {
        if($this->getCrmStatusList()){
            $sourceList = $this->getCrmStatusList(self::CRM_DEAL_TYPE);
            return $sourceList[$_name];
        }

        return false;
    }

    /**
     * Получение типа сделки
     * @param string $_name
     * @return string
     * @throws \Exception
     */
    public function getDealStage($_name)
    {
        if($this->getCrmStatusList()){
            $sourceList = $this->getCrmStatusList(self::CRM_DEAL_STAGE);
            return $sourceList[$_name];
        }

        return false;
    }

    /**
     * Поля контакта
     * @return array
     * @throws \Exception
     */
    protected function _getCrmContactFields($_name = null)
    {
        if(is_null($this->_contactFieldsList)){
            $url = 'rest/crm.contact.fields';
            $data = array(
                'auth' => $this->getAccessToken()
            );

            $res = $this->sendRequest($url, $data);

            if(property_exists($res, 'result')){
                $this->_contactFieldsList = $res->result;
            }else{
                throw new \Exception('Ошибка получения данных');
            }
        }

        return $_name ? $this->_contactFieldsList[$_name] : $this->_contactFieldsList;
    }

    /**
     * Поля сделки
     * @return array|string
     * @throws \Exception
     */
    protected function _getCrmDealFields($_name = null)
    {
        if(is_null($this->_dealFieldsList)){
            $url = 'rest/crm.deal.fields';
            $data = array(
                'auth' => $this->getAccessToken()
            );

            $res = $this->sendRequest($url, $data);

            if(property_exists($res, 'result')){
                $this->_dealFieldsList = $res->result;
            }else{
                throw new \Exception('Ошибка получения данных');
            }
        }

        return $_name ? $this->_dealFieldsList[$_name] : $this->_dealFieldsList;
    }

    /**
     * Поиск контакта по фильтру
     * @param string $_phone
     * @return bool|int
     * @throws \Exception
     */
    public function getContact($_filter, $_select)
    {
        $url = 'rest/crm.contact.list';

        $data = array(
            'auth' => $this->getAccessToken(),
            'filter' => $_filter,
            'select' => $_select
        );

        $res = $this->sendRequest($url, $data);

        return $res;
    }

    /**
     * Добавление контакта
     * @param array $_fields
     * @return bool
     * @throws \Exception
     */
    public function addContact($_fields)
    {
        $url = 'rest/crm.contact.add';

        $data = array(
            'auth' => $this->getAccessToken(),
            'fields' => $_fields
        );

        $res = $this->sendRequest($url, $data);
        if(property_exists($res, 'result')){
            $contactId = $res->result;
        }else{
            $this->_msg = $res->error_description;
            return false;
            //throw new Exception('Ошибка при добавлении контакта');
        }

        return $contactId;
    }

    /**
     * Добавление сделки
     * @param array $_fields
     * @throws \Exception
     * @return bool
     */
    public function addDeal($_fields)
    {
        $url = 'rest/crm.deal.add';

        $data = array(
            'auth' => $this->getAccessToken(),
            'fields' => $_fields
        );

        $res = $this->sendRequest($url, $data);

        if($res->result){
            return $res->result;
        }else{
            throw new \Exception('Ошибка при добавлении сделки');
        }

        return false;
    }
}