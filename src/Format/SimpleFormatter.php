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
				'matches' => $ipn->getInvoiceMatches(),
				'ipn' => $ipn->jsonSerialize(),
				'timestamp' => time()
			]
		];
		if ($request)
		{
			$response['data']['request'] = [
				'url' => $request->url(),
				'method' => $request->method(),
				'scheme' => $request->getScheme(),
				'origin' => $request->header('origin'),
			];
		}
		return $response;
	}
}