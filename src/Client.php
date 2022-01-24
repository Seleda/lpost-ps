<?php


namespace Seleda\LPostPs;

use \Configuration;
use \Seleda\LPostPs\Configuration as ConfL;
use \Seleda\LPostPs\Logger;

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
        if (!$token || !isset($token['valid_till']) || time() + 60 > strtotime($token['valid_till'])) { // 1 минута погрешность
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

    public function getReceivePoints()
    {

    }

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

        return $response['JSON_TXT'];

    }
}