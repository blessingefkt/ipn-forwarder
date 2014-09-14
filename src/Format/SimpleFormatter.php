<?php namespace IpnForwarder\Format;


use IpnForwarder\IpnEntity;

class SimpleFormatter implements JsonFormatter {
	/**
	 * @var  \Illuminate\Http\Request
	 */
	protected $httpRequest;

	public function formatJsonResponse(IpnEntity $ipn)
	{
		$response = [
			'status' => 'ok',
			'code' => 200,
			'data' => [
				'transaction_id' => $ipn->txn_id,
				'transaction_type' => $ipn->txn_type,
				'matches' => $ipn->getInvoiceMatches(),
				'ipn' => $ipn->jsonSerialize(),
				'timestamp' => date('Y-m-d h:i:s', time())
			]
		];
		if ($this->httpRequest)
		{
			$response['data'] = $this->setRequestData($response['data']);
		}
		return $response;
	}

	/**
	 * @param \Illuminate\Http\Request $httpRequest
	 */
	public function setRequest($httpRequest)
	{
		$this->httpRequest = $httpRequest;
	}

	/**
	 * @param $data
	 * @return array
	 */
	protected function setRequestData(array $data)
	{
		$data['original_headers'] = $this->httpRequest->headers->all();
		$data['request'] = [
			'host' => $this->httpRequest->getHost(),
			'method' => $this->httpRequest->method(),
			'scheme' => $this->httpRequest->getScheme()
		];
		return $data;
	}
}