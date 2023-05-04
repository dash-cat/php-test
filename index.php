<?php

class TooManyRequests extends Exception
{
}

class Dadata
{
    private $token;
    private $handle;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function init()
    {
        $this->handle = curl_init();
        curl_setopt($this->handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->handle, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Accept: application/json",
            "Authorization: Token " . $this->token,
        ));
        curl_setopt($this->handle, CURLOPT_POST, 1);
    }

    /*Поиск наименования организации по ИНН https://dadata.ru/api/find-party/ 
    Метод получает ИНН организации. Возвращает наименование 
    организации (suggestions->value из ответа DaData) */
    public function findByInn($fields)
    {
        $url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/party";
        return $this->executeRequest($url, $fields);
    }

    /* Поиск наименования Банка по БИК https://dadata.ru/api/find-bank/
    Метод получает БИК Банка
    Возвращает наименование банка (suggestions->value из ответа DaData)*/
    public function findByBik($fields)
    {
        $url = "https://suggestions.dadata.ru/suggestions/api/4_1/rs/findById/bank";
        return $this->executeRequest($url, $fields);
    }

    /**
     * Close connection.
     */
    public function close()
    {
        curl_close($this->handle);
    }

    private function executeRequest($url, $fields)
    {
        curl_setopt($this->handle, CURLOPT_URL, $url);
        curl_setopt($this->handle, CURLOPT_POST, 1);
        curl_setopt($this->handle, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = $this->exec();
        $result = json_decode($result, true);
        return $result;
    }

    private function exec()
    {
        $result = curl_exec($this->handle);
        $info = curl_getinfo($this->handle);
        if ($info['http_code'] == 429) {
            throw new TooManyRequests();
        } elseif ($info['http_code'] != 200) {
            throw new Exception('Request failed with http code ' . $info['http_code'] . ': ' . $result);
        }
        return $result;
    }
}

$token = "4b8a7b9d1a53b30e6faf1b9ebd2330e1758e5530";
$inn = '7707083893';// ИНН
$bik = '044525225';// БИК

$dadata = new Dadata($token);
$dadata->init();

// Поиск наименования организации по ИНН 
$fields = array("query" => $inn, "count" => 1);
$result = $dadata->findByInn($fields);
print_r($result['suggestions'][0]['value']);
print_r('<br>');


// Поиск наименования Банка по БИК
$fields = array("query" => $bik, "count" => 1);
$result = $dadata->findByBik( $fields);
print_r($result['suggestions'][0]['value']);


$dadata->close();

?>