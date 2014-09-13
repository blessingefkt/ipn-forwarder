<?php namespace IpnForwarder;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use IpnForwarder\Format\JsonFormatter;
use IpnForwarder\Format\SimpleFormatter;

class Forwarder {
	/**
	 * @var \GuzzleHttp\Client
	 */
	private $guzzle;
	/**
	 * @var \Illuminate\Http\Request
	 */
	private $request;
	/** @var  string */
	private $customHeader = 'X-IpnEntity-FORWARDER', $key;
	/** @var int */
	protected $maxRequests = 15;
	/** @var \IpnForwarder\Format\JsonFormatter */
	protected $formatter;

	public function __construct(Client $guzzle)
	{
		$this->guzzle = $guzzle;
		$this->formatter = new SimpleFormatter();
	}

	/**
	 * @param IpnEntity $ipn
	 * @param \Illuminate\Http\Request $request
	 * @return bool
	 */
	public function forwardIpn(IpnEntity $ipn, Request $request = null)
	{
		$urls = $ipn->getForwardUrls();
		if (!empty($urls))
		{
			$this->send($urls, $this->formatter->formatJsonResponse($ipn, $request));
			return true;
		}
		return false;
	}

	/**
	 * @param \IpnForwarder\Format\JsonFormatter $formatter
	 * @return $this
	 */
	public function setFormatter(JsonFormatter $formatter)
	{
		$this->formatter = $formatter;
		return $this;
	}

	/**
	 * @param array $urls
	 * @param array $response
	 */
	protected function send(array $urls, array $response)
	{
		$requests = [];
		$options = ['json' => $response];
		foreach ($urls as $url)
		{
			$request = $this->guzzle->createRequest('post', $url, $options);
			$request->setHeader($this->customHeader, $this->getKey());
			$requests[] = $request;
		}
		$this->guzzle->sendAll($requests, ['parallel' => $this->maxRequests]);
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	public function getKey()
	{
		if (empty($this->key))
		{
			throw new Exception('A key must be defined.');
		}
		return $this->key;
	}

	/**
	 * @param string $service
	 * @return $this
	 */
	public function setKey($service)
	{
		$this->key = $service;
		return $this;
	}
}