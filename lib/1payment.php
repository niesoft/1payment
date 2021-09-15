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
		return $sign = "init_form" . md5( http_build_query($data) ) . $this->api_key;
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
		$result = file_get_contents($this->init_url, false, $context);

		if ($debug) $this->saveLog($result, $http_response_header);

		$result = json_decode($result, true);
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

}