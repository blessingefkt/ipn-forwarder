<?php namespace IpnForwarder;

use GuzzleHttp\Client;
use GuzzleHttp\Stream\Stream;
use IpnForwarder\Format\JsonFormatter;

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
	}

	/**
	 * @param IpnEntity $ipn
	 * @return bool
	 */
	public function forwardIpn(IpnEntity $ipn)
	{
		$urls = $ipn->getForwardUrls();
		if (!empty($urls))
		{
			$requests = [];
			foreach ($urls as $url)
			{
				$request = $this->guzzle->createRequest('post', $url);
				$request->setHeader($this->customHeader, $this->getKey());
				if (in_array($url, $this->disabledJsonFormatting))
				{
					$request->getQuery()->merge($ipn->toArray());
				}
				else
				{
					$request->setHeader('content-type', 'application/json');
					if ($this->formatter)
					{
						$response = $this->formatter->formatJsonResponse($ipn);
					}
					else
					{
						$response = ['ipn' => $ipn->toArray()];
					}
					$request->setBody(Stream::factory(json_encode($response)));
				}
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
	 * @return \IpnForwarder\Format\JsonFormatter
	 */
	public function getFormatter()
	{
		return $this->formatter;
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