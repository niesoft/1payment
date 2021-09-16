<?php 
class OnePayment {

	private string $init_url = "https://api.1payment.com/init_form";
	private int $timeout = 60; 

	private array $data = [
		'partner_id' => null, // ID партнера
		'project_id' => null, // ID проекта
		'amount' => null, // сумма в валюте проекта
		'description' => null, // описание платежа (опционально)
		'success_url' => null, // url возврата плательщика после успешной оплаты, (опционально)
		'failure_url' => null, // url возврата плательщика после ошибки оплаты, (опционально)
		'trusted_user' => null, // параметр, определяющий уровень доверия к плателищику (подключается опционально, подробности уточняйте у своего менеджера)
		'user_data' => 0, // уникальное значение, например, идентификатор платежа на стороне партнера
		'token' => null, // id токена для отображения сохраненной карты (опционально)
		'shop_url' => null, // URL сайта источника платежа (опционально)
		'sign' => null, // подпись
	];

	private array $errors_code = [
		'1' => "Общая ошибка: обратитесь в поддержку для уточнения",
		'2' => "Ошибка проверки цифровой подписи",
		'3' => "Метод недоступен",
		'4' => "Ошибка определения партнера",
		'5' => "Ошибка определения проекта",
		'6' => "Платежный метод для данного проекта недоступен",
		'7' => "Данный способ выплат недоступен для проекта",
		'8' => "Некорректный параметр user_data",
		'9' => "Недостаточно средств на балансе для проведения выплаты",
		'10' => "Транзакция не найдена",
		'11' => "Оплата для данного источника недоступна",
		'99' => "Unable to send request"
	];

	private int $lastError = 0;

	private array $sendData = [];

	function __construct(private string $partner_id, private string $project_id, private string $api_key)
	{
		$this->data['partner_id'] = $partner_id;
		$this->data['project_id'] = $project_id;
	}

	public function setData(array $data) : object
	{

		$this->sendData = $this->data;
		foreach ($data as $key => $value) {
			$this->sendData[$key] = $value;
		}
		$this->sendData = $this->removeEmpty($this->sendData);
		$this->sendData['sign'] = $this->getSign($this->sendData);

		return $this;
	}
	
	private function removeEmpty(array $data) : array
	{
		foreach ($data as $key => $value) {
			if (is_null($value)) unset($data[$key]);
		}
		return $data;
	}
	
	private function getSign(array $data) : string
	{
		ksort($data);
		$sign = implode("&", array_map(fn ($key, $value) => $key . "=" . $value, array_keys($data), array_values($data)));
		return md5( "init_form" . $sign . $this->api_key);
	}

	public function getForm(bool $debug = false) : string | bool
	{
		$context = stream_context_create([
			'http' => [
				'method' => "POST",
				'header' => "Content-Type: application/json\r\n",
				'content' => json_encode($this->sendData),
    			'timeout' => $this->timeout
			]
		]);

		$result = @file_get_contents($this->init_url, false, $context);
		if ($result == false){
			$this->lastError = 99;
			return false;
		}
		if ($debug) $this->saveLog($result, $http_response_header);

		$result = json_decode($result, true);
		if (isset($result['error_code'])) $this->lastError = intval($result['error_code']);

		return isset($result['url']) ? $result['url'] : false;
	}

	private function arrayToString(array $data) : string
	{
		$result = "";
		foreach ($data as $key => $value) {
			$result .= "{$key} => \t{$value}\r\n";
		}
		return $result;
	}

	private function saveLog($result, $response)
	{
		$filename = dirname(__DIR__) . "/1paymentInit-" . date("d-m-y", time()) . ".txt";
		$content = date("d.m.y H:i:s", time()) . "\r\nOUTPUT: \r\n" . $this->arrayToString($this->sendData) . "\r\n\r\n" . "INPUT: \r\n" . $this->arrayToString($response) . "\r\n" . $result . "\r\n\r\n";
		file_put_contents($filename, $content, FILE_APPEND);
	}

	public function getLastError() : array
	{
		$error = ['code' => $this->lastError];
		$error['message'] = isset($this->errors_code[$this->lastError]) ? $this->errors_code[$this->lastError] : 'unknown';
		return $error;
	}

}