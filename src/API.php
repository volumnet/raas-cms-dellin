<?php
/**
 * API "Деловых Линий"
 */
namespace RAAS\CMS\DelLin;

use Exception;
use RAAS\Application;

/**
 * Класс API "Деловых Линий"
 */
class API
{
    /**
     * Адрес API
     */
    const API_URL = 'https://api.dellin.ru/';

    /**
     * Ключ приложения
     * @var string
     */
    protected $appKey = null;

    /**
     * ID# сессии
     */
    protected $sessionId = null;

    /**
     * Конструктор класса
     * @param string $appKey Ключ приложения
     */
    public function __construct($appKey)
    {
        if ($appKey) {
            $this->appKey = $appKey;
        }
    }

    /**
     * "Сырое" выполнение метода
     * @param string $url Абсолютный URL метода
     * @param string|null $data Данные запроса
     * @return string
     */
    public function rawMethod($url, $data = [])
    {
        $ch = curl_init($url);
        if ($data) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_VERBOSE, 1);
        $st = microtime(1);
        $result = curl_exec($ch);
        // var_dump($url, microtime(1) - $st); exit;

        if (Application::i()->debug) {
            $logFilename = Application::i()->baseDir . '/logs/' . date('Y-m-d-H-i-s') . ' dellin.txt';
            $logData = ['url' => $url, 'request' => $data, 'response' => $result];
            file_put_contents($logFilename, var_export($logData, true));
        }
        return $result;
    }


    /**
     * Вызывает метод по относительному адресу с набором параметров
     * @param string $name Имя (относительный адрес) метода, без расширения
     * @param array $data Данные для отправки
     * @param int $version Версия API
     * @param bool $throw Выбрасывать исключение при ошибке
     * @return array Данные ответа
     */
    public function method($name, array $data = [], $version = 2, $throw = false)
    {
        $url = static::API_URL . 'v' . $version . '/' . $name . '.json';
        $request = $data;
        if ($this->appKey) {
            $request['appkey'] = $this->appKey;
        }
        if ($this->sessionId) {
            $request['sessionID'] = $this->sessionId;
        }
        $json = json_encode($request);
        $response = $this->rawMethod($url, $json);
        $result = json_decode($response, true);
        if ($throw && $response['errors']) {
            $errorCode = null;
            $errorTitle = '';
            if (is_array($response['errors'])) {
                if (isset($response['errors'][0])) {
                    $error = $response['errors'][0];
                } else {
                    $error = $response['errors'];
                }
                if (isset($error['title'])) {
                    $errorTitle .= $error['title'];
                    if ($error['detail']) {
                        $errorTitle .= ': ' . $error['detail'];
                    }
                }
                if (isset($error['code'])) {
                    $errorCode = (int)$error['code'];
                }
            } else {
                $errorTitle = $response['error'];
            }
            throw new Exception($errorTitle, $errorCode);
        }
        return $result;
    }


    /**
     * Авторизация пользователя
     * @param string $login Логин пользователя
     * @param string $password Пароль пользователя
     */
    public function login($login, $password)
    {
        $request = [
            'login' => $login,
            'password' => $password,
        ];
        $response = $this->method('auth/login', $request, 3, true);
        $this->sessionId = $response['data']['sessionID'];
        return $response;
    }


    public function logout()
    {
        if (!$this->sessionId) {
            throw new Exception("Session ID is not set");
        }
        $response = $this->method('auth/logout', [], 3, true);
        $this->sessionId = null;
        return $response;
    }


    public function sessionInfo()
    {
        if (!$this->sessionId) {
            throw new Exception("Session ID is not set");
        }
        $response = $this->method('auth/session_info', [], 3, true);
        return $response;
    }
}
