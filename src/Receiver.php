<?php namespace IpnForwarder;

use Illuminate\Http\Request;
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

	/**
	 * @param \Illuminate\Http\Request $request
	 * @return array
	 */
	public function listen(Request $request)
	{
		if (!$request->isMethod('post'))
		{
			$response = ['status' => 'error', 'msg' => 'invalid request method'];
		}
		else
		{
			$msg = $this->makeMessage($request);
			/** @var IpnEntity $ipn */
			$ipn = $this->ipnProcessor->processRequest($msg);

			$logData = [$ipn->invoice, $ipn->txn_id, $ipn->payment_date];

			if ($this->ipnProcessor->isValidIpn())
			{
				if ($this->ipnForwarder->forwardIpn($ipn, $request))
				{
					$response = $this->makeForwardedResponse($logData, $ipn);

				}
				else
				{
					$response = $this->makeNotForwardedResponse($logData, $ipn);
				}
			}
			else
			{
				$response = $this->makeErrorResponse($logData, $ipn);
			}
		}
		return $response;
	}

	/**
	 * @param Request $request
	 * @return Message
	 */
	protected function makeMessage(Request $request)
	{
		$msg = new Message($request->query());
		return $msg;
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