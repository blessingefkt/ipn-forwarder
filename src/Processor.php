<?php namespace IpnForwarder;

use PayPal\Ipn\Listener;
use PayPal\Ipn\Message;
use PayPal\Ipn\Verifier;

class Processor {
	/** @var array | \IpnForwarder\IpnSubscriber[] */
	protected $subscribers = [];
	/**
	 * @var \IpnForwarder\UrlCollection
	 */
	private $urlCollection;
	/**
	 * @var \PayPal\Ipn\Listener
	 */
	private $listener;
	/**
	 * @var bool
	 */
	private $validIpn = false,
		$skipVerification = false;
	/** @var  \PayPal\Ipn\Verifier */
	private $verifier;

	public function __construct(UrlCollection $urlCollection, Listener $listener)
	{
		$this->urlCollection = $urlCollection;
		$this->listener = $listener;
	}

	/**
	 * @param \PayPal\Ipn\Message $ipnMsg
	 * @return IpnEntity
	 */
	public function processRequest(Message $ipnMsg)
	{
		$this->verifier->setIpnMessage($ipnMsg);
		$ipn = new IpnEntity($ipnMsg, $this->listener->getReport());
		$this->validIpn = $this->skipVerification ? true : $this->listener->processIpn();
		if ($this->validIpn)
		{
			$this->respondToValidIpn($ipn);
		}
		else
		{
			$this->respondToInvalidIpn($ipn);
		}
		return $ipn;
	}

	/**
	 * @param IpnEntity $ipn
	 * @return array|bool
	 */
	protected function respondToValidIpn(IpnEntity $ipn)
	{
		$urls = $this->urlCollection->findListeners($ipn->invoice);
		$ipn->setInvoiceMatches($this->urlCollection->getMatchedParts());
		if ($urls)
		{
			$ipn->setForwardUrls($urls);
			foreach ($this->subscribers as $subscriber)
				$subscriber->onValidIpn($ipn);
		}
		return $urls;
	}

	/**
	 * @param IpnEntity $ipn
	 */
	protected function respondToInvalidIpn(IpnEntity $ipn)
	{
		foreach ($this->subscribers as $subscriber)
			$subscriber->onInvalidIpn($ipn);
	}

	/**
	 * @param IpnSubscriber $subscriber
	 * @return $this
	 */
	public function addSubscriber(IpnSubscriber $subscriber)
	{
		$this->subscribers[] = $subscriber;
		return $this;
	}

	/**
	 * @param Verifier $verifier
	 * @return $this
	 */
	public function setVerifier(Verifier $verifier)
	{
		$this->verifier = $verifier;
		$this->listener->setVerifier($verifier);
		return $this;
	}

	/**
	 * @return boolean
	 */
	public function isValidIpn()
	{
		return $this->validIpn;
	}

	/**
	 * @return \PayPal\Ipn\Listener
	 */
	public function getListener()
	{
		return $this->listener;
	}

	/**
	 * @return \IpnForwarder\UrlCollection
	 */
	public function getUrlCollection()
	{
		return $this->urlCollection;
	}

	public function setSandbox()
	{
		$this->verifier->setEnvironment(Verifier::SANDBOX_HOST);
	}

	public function setProduction()
	{
		$this->verifier->setEnvironment(Verifier::PRODUCTION_HOST);
	}

	public function getEnvironment()
	{
		return $this->verifier->getEnvironment();
	}

	public function getVerifier()
	{
		return $this->verifier;
	}


	public function skipVerification($value = true)
	{
		$this->skipVerification = (bool)$value;
		return $this;
	}
} 