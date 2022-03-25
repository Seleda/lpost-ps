<?php


namespace Seleda\LPostPs;

use \Configuration;
use Matrix\Exception;
use \Seleda\LPostPs\Configuration as ConfL;
use \Seleda\LPostPs\Logger;
use \Validate;

class Client
{
    private static $instance;

    private $token;
    private $domen;
    private $logger;

    private function __construct()
    {
        $this->domen = sprintf('https://api%s.l-post.ru/', ConfL::get('test') ? 'test' : '');

        $config_name = sprintf('LPOST_API_TOKEN%s', ConfL::get('test') ? '_TEST' : '');

        $token = json_decode(Configuration::get($config_name), true);

        $data = array('method' => 'Auth', 'secret' => ConfL::get('secret_key'));

        // Л-Пост возвращает valid_till по времени сервера клиента
        // requirement 3.9 Обновлять токен каждые 55 минут
        if (!$token || !isset($token['valid_till']) || time() + 300 > strtotime($token['valid_till'])) { // 300 = 5 минут
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => $this->domen,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $data,
            ));

            $response = curl_exec($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            Logger::addMessage('Auth', 'Code '.$code, json_encode($data), $response);

            Configuration::updateValue($config_name, $response);

            $token = json_decode($response, true);
        }
        $this->token = isset($token['token']) ? $token['token'] : false;
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // req 3.8 Обновлять каждые 24 часа
    public function getPickupPoints($params = array())
    {
        $json = urlencode(json_encode($params));

        $url = $this->domen.'?method=GetPickupPoints&ver=1&token='.$this->token.'&json='.$json;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        Logger::addMessage('GetPickupPoints', 'Code '.$code, $url, $response);

        $response = json_decode($response, true);
        if (!isset($response['JSON_TXT'])) {
            return false;
        }

        $points = json_decode($response['JSON_TXT'], true);

        return $points['PickupPoint'];
    }

    // req 3.10 Обновлять каждые 24 часа
    public function getReceivePoints($params = array())
    {
        $json = urlencode(json_encode($params));

        $url = $this->domen.'?method=GetReceivePoints&ver=1&token='.$this->token.'&json='.$json;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        Logger::addMessage('getReceivePoints', 'Code '.$code, $url, $response);

        $response = json_decode($response, true);
        if (!isset($response['JSON_TXT'])) {
            return false;
        }

        $points = json_decode($response['JSON_TXT'], true);

        return $points['ReceivePoints'];
    }

    public function GetServicesCalc($params = array())
    {
        $json = urlencode(json_encode($params));

        $url = $this->domen.'?method=GetServicesCalc&ver=1&token='.$this->token.'&json='.$json;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        Logger::addMessage('getServicesCalc', 'Code '.$code, json_encode($params, JSON_UNESCAPED_UNICODE), $response);

        $response = json_decode($response, true);
        if (!isset($response['JSON_TXT'])) {
            return false;
        }
        $result = json_decode($response['JSON_TXT'], true);
        return $result['JSON_TXT'][0];
    }

    public function CreateOrders($params)
    {
        $request = array();
        $request['token'] = $this->token;
        $request['method'] = 'CreateOrders';
        $request['ver'] = 1;
        $request['json'] = json_encode($params);

        $json = urlencode(json_encode($params));

        $url = $this->domen.'?method=CreateOrders&ver=1&token='.$this->token.'&json='.$json;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $request,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: multipart/form-data'
            ),
        ));

        $response = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        Logger::addMessage('CreateOrders', 'Code '.$code, json_encode($params, JSON_UNESCAPED_UNICODE), $response);

        $response = json_decode($response, true);
        if (!isset($response['JSON_TXT'])) {
            return false;
        }
        $result = json_decode($response['JSON_TXT'], true);
        return $result[0];
    }

    public function UpdateOrders($params)
    {
        $json = urlencode(json_encode($params));
        $request = 'token='.$this->token.'&method=UpdateOrders&ver=1&json='.$json;

        $curl = curl_init();

        curl_setopt_array(
            $curl,
            array(
                CURLOPT_URL => 'https://api.l-post.ru/',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'PUT',
                CURLOPT_POSTFIELDS => $request,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/x-www-form-urlencoded'
                ),
            )
        );

        $response = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        Logger::addMessage('UpdateOrders', 'Code '.$code, json_encode($params, JSON_UNESCAPED_UNICODE), $response);

        $response = json_decode($response, true);
        if (!isset($response['JSON_TXT'])) {
            return false;
        }
        $result = json_decode($response['JSON_TXT'], true);
        return $result[0];
    }

    public function GetStateOrders($orders)
    {
        if (is_array($orders)) {
            $json = json_encode($orders);
        } elseif (Validate::isJson($orders)) {
            $json = $orders;
        } else {
            throw new Exception('Bad params!!!');
        }
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.l-post.ru/?method=GetStateOrders&token='.$this->token.'&ver=1&json='.$json,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
        ));

        $response = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        Logger::addMessage('GetStateOrders', 'Code '.$code, $json, $response);

        $response = json_decode($response, true);
        if (!isset($response['JSON_TXT'])) {
            return false;
        }
        $result = json_decode($response['JSON_TXT'], true);
        return $result[0];
    }

    public function DeleteOrders($params)
    {
        $request = 'token='.$this->token.'&ver=1&method=DeleteOrders&json='.json_encode($params);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $this->domen,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_POSTFIELDS => $request,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        $response = curl_exec($curl);

        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        Logger::addMessage('DeleteOrders', 'Code '.$code, json_encode($params, JSON_UNESCAPED_UNICODE), $response);

        $response = json_decode($response, true);
        if (!isset($response['JSON_TXT'])) {
            return false;
        }
        $result = json_decode($response['JSON_TXT'], true);
        return $result[0];
    }

    public static function geoCode($address)
    {
        return json_decode(file_get_contents('https://geocode-maps.yandex.ru/1.x?geocode='.(urlencode($address)).'&apikey='.ConfL::get('yandex_api_key').'&sco=longlat&format=json&lang=ru_RU'), true);
    }
}
