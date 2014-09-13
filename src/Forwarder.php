<?php namespace IpnForwarder;

use GuzzleHttp\Client;
use GuzzleHttp\Stream\Stream;
use Illuminate\Http\Request;
use IpnForwarder\Format\JsonFormatter;
use IpnForwarder\Format\SimpleFormatter;

class Forwarder {
	/** @var \GuzzleHttp\Client */
	private $guzzle;
	/** @var  string */
	private $customHeader = 'X-IPN-FORWARDER', $key;
	/** @var int */
	protected $maxRequests = 15;
	/** @var \IpnForwarder\Format\JsonFormatter */
	protected $formatter;
	/** @var array */
	protected $disabledJsonFormatting = [];

	public function __construct(Client $guzzle)
	{
		$this->guzzle = $guzzle;
		$this->formatter = new SimpleFormatter();
	}

	/**
	 * @param IpnEntity $ipn
	 * @param \Illuminate\Http\Request $httpRequest
	 * @return bool
	 */
	public function forwardIpn(IpnEntity $ipn, Request $httpRequest = null)
	{
		$urls = $ipn->getForwardUrls();
		if (!empty($urls))
		{
			$requests = [];
			foreach ($urls as $url)
			{
				$request = $this->guzzle->createRequest('post', $url);
				if (in_array($url, $this->disabledJsonFormatting))
				{
					$request->getQuery()->merge($ipn->toArray());
				}
				else
				{
					$request->setHeader('content-type', 'application/json');
					$response = $this->formatter->formatJsonResponse($ipn, $httpRequest);
					$request->setBody(Stream::factory(json_encode($response)));
				}
				$request->setHeader($this->customHeader, $this->getKey());
				$requests[] = $request;
			}
			$this->guzzle->sendAll($requests, ['parallel' => $this->maxRequests]);
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
	 * Disable json formatter forwarding IPN data to the following url
	 * @param $url
	 * @return $this
	 */
	public function disableFormatting($url)
	{
		$this->disabledJsonFormatting[] = $url;
		return $this;
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