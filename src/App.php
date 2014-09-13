<?php namespace IpnForwarder;

use Illuminate\Container\Container;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Response;
use PayPal\Ipn\Verifier;

/**
 * Class App
 * @package Receiver
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
		$this->bindService('log', \Monolog\Logger::class, function ($this)
		{
			$log = new \Monolog\Logger($this->getName());
			$log->pushHandler(new \Monolog\Handler\StreamHandler($this['log_file'], \Monolog\Logger::DEBUG));
			return $log;
		});
		$this->bindAliased('request', \Illuminate\Http\Request::class, function ($this)
		{
			return \Illuminate\Http\Request::createFromGlobals();
		});
		$this->bindAliased('guzzle', \GuzzleHttp\Client::class, function ($app)
		{
			$client = new \GuzzleHttp\Client();
			$client->getEmitter()->attach($app->make(\IpnForwarder\Guzzle\GuzzleSubscriber::class));
			return $client;
		});
		$this->bindService('paypal', \PayPal\Ipn\Listener::class);
		$this->bindService('urls', \IpnForwarder\UrlCollection::class);
		$this->bindService('ipnForwarder', \IpnForwarder\Forwarder::class);
		$this->bindService('ipnProcessor', \IpnForwarder\Processor::class);
	}

	public function shutdown()
	{
		exit();
	}

	/**
	 * @param $msg
	 * @param int $code
	 * @return \Illuminate\Http\JsonResponse
	 */
	public function makeErrorResponse($msg, $code = 400)
	{
		return $this->makeResponse([
			'status' => 'error',
			'msg' => $msg,
			'code' => $code
		]);
	}

	public function logException(\Exception $ex)
	{
		$this['log']->error($ex->getMessage() . ' -- ' . $ex->getTraceAsString(),
			[$ex->getCode(), $ex->getLine(), $ex->getFile()]);
	}

	/**
	 * @param array $data
	 * @return JsonResponse
	 */
	public function makeResponse(array $data)
	{
		return Response::json($data);
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


	public function bindAliased($alias, $abstract, $concrete = null)
	{
		$this->bind($abstract, $concrete);
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