<?php namespace IpnForwarder;

use Monolog\Logger;
use PayPal\Ipn\Message;

class Receiver {
	/** @var  \Monolog\Logger */
	protected $logger;
	/**
	 * @var Processor
	 */
	private $ipnProcessor;
	/**
	 * @var Forwarder
	 */
	private $ipnForwarder;

	public function __construct(Processor $ipnProcessor, Forwarder $ipnForwarder)
	{
		$this->ipnProcessor = $ipnProcessor;
		$this->ipnForwarder = $ipnForwarder;
	}

	public static function makeMessage(array $data)
	{
		$msg = new Message($data);
		return $msg;
	}

	/**
	 * @param \PayPal\Ipn\Message $msg
	 * @return array
	 */
	public function listen(Message $msg)
	{
		/** @var IpnEntity $ipn */
		$ipn = $this->ipnProcessor->processRequest($msg);

		$logData = [$ipn->invoice, $ipn->txn_id, $ipn->payment_date];

		if ($this->ipnProcessor->isValidIpn())
		{
			if ($this->ipnForwarder->forwardIpn($ipn))
			{
				return $this->makeForwardedResponse($logData, $ipn);
			}
			return $this->makeNotForwardedResponse($logData, $ipn);
		}
		return $this->makeErrorResponse($logData, $ipn);
	}

	/**
	 * @return \IpnForwarder\Forwarder
	 */
	public function forwarder()
	{
		return $this->ipnForwarder;
	}

	/**
	 * @return \IpnForwarder\Processor
	 */
	public function processor()
	{
		return $this->ipnProcessor;
	}

	public function setLogger(Logger $logger)
	{
		$this->logger = $logger;
	}

	protected function log($message, $data = null)
	{
		if ($this->logger)
		{
			$this->logger->info($message, $data);
		}
	}

	/**
	 * @param $logData
	 * @param IpnEntity $ipn
	 * @return array
	 */
	protected function makeErrorResponse($logData, $ipn)
	{
		$msg = 'IpnEntity is invalid';
		$response = ['status' => 'ok', 'msg' => $msg];
		$this->log($msg, $logData);
		return $response;
	}

	/**
	 * @param IpnEntity $ipn
	 * @param $logData
	 * @return array
	 */
	protected function makeForwardedResponse($logData, $ipn)
	{
		$msg = 'Notified ' . count($ipn->getForwardUrls()) . ' urls.';

		$response = ['status' => 'ok', 'msg' => $msg];
		$this->log($msg, $logData);
		return $response;
	}

	/**
	 * @param $logData
	 * @param IpnEntity $ipn
	 * @return array
	 */
	protected function makeNotForwardedResponse($logData, $ipn)
	{
		$msg = 'No listener was found';
		$response = ['status' => 'ok', 'msg' => $msg];
		$this->log($msg, $logData);
		return $response;
	}
} 