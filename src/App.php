<?php namespace IpnForwarder;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\Response;
use PayPal\Ipn\Message;
use PayPal\Ipn\Verifier;

/**
 * Class App
 * @package IpnListener
 */
class App extends Container {
	/** @var string */
	private $name;
	/** @var  array */
	private $response = [];

	public function __construct($name, $path, $logFile = 'logs/ipn-forwarder.log')
	{
		$this->name = $name;
		$this->instance('path', rtrim($path, DIRECTORY_SEPARATOR));
		$this->instance('log_file', $path . DIRECTORY_SEPARATOR . $logFile);
	}

	public function boot()
	{
		require __DIR__ . '/../bindings.php';
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function run()
	{
		if (!$this->request->isMethod('post'))
		{
			$this->response = ['status' => 'error', 'msg' => 'invalid request method'];
		}
		else
		{
			$msg = new Message($this->request->query());
			/** @var IpnEntity $ipn */
			$ipn = $this->ipnProcessor->processRequest($msg);

			if ($this->ipnProcessor->isValidIpn())
			{
				if ($this->ipnForwarder->forwardIpn($ipn, $this->request))
				{
					$msg = 'Notified ' . count($ipn->getForwardUrls()) . ' urls.';
				}
				else
				{
					$msg = 'No listener was found';
				}
				$this->response = ['status' => 'ok', 'msg' => $msg];
			}
			else
			{
				$this->response = ['status' => 'ok', 'msg' => 'IpnEntity is invalid'];
			}
		}
		return $this->getResponse();
	}

	public function shutdown()
	{
		exit();
	}

	/**
	 * @param $msg
	 * @param int $code
	 */
	public function setErrorResponse($msg, $code = 400)
	{
		$this->response = [
			'status' => 'error',
			'msg' => $msg,
			'code' => $code
		];
	}

	public function logException(\Exception $ex)
	{
		$this['log']->error($ex->getMessage() . ' -- ' . $ex->getTraceAsString(),
			[$ex->getCode(), $ex->getLine(), $ex->getFile()]);
	}

	/**
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function getResponse()
	{
		return Response::json($this->response);
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		$this->assertAppName();
		return $this->name;
	}

	/**
	 * @param $alias
	 * @param $abstract
	 * @param null $concrete
	 */
	public function bindService($alias, $abstract, $concrete = null)
	{
		if ($concrete instanceof \Closure)
		{
			$this->bindShared($abstract, $concrete);
		}
		else
		{
			$this->singleton($abstract, $concrete);
		}
		$this->alias($abstract, $alias);
	}


	protected function assertAppName()
	{
		if (empty($this->name))
		{
			throw new Exception('Please set the application\'s name.');
		}
	}

	public function __get($name)
	{
		return $this->make($name);
	}
}