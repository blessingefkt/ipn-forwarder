<?php namespace IpnForwarder\Format;


use Illuminate\Http\Request;
use IpnForwarder\IpnEntity;

class SimpleFormatter implements JsonFormatter {

	public function formatJsonResponse(IpnEntity $ipn, Request $request = null)
	{
		$response = [
			'status' => 'ok',
			'code' => 200,
			'data' => [
				'transaction_id' => $ipn->txn_id,
				'transaction_type' => $ipn->txn_type,
				'matches' => $ipn->getInvoiceMatches(),
				'ipn' => $ipn->jsonSerialize(),
				'timestamp' => time()
			]
		];
		if ($request)
		{
			$response['data']['original_headers'] = $request->headers->all();
			$response['data']['request'] = [
				'url' => $request->url(),
				'host' => $request->getHost(),
				'method' => $request->method(),
				'scheme' => $request->getScheme()
			];
		}
		return $response;
	}
}