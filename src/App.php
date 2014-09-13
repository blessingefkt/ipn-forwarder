<?php namespace IpnForwarder;

use Illuminate\Container\Container;
use PayPal\Ipn\Message;
use PayPal\Ipn\Verifier;

/**
 * Class App
 * @package IpnListener
 * @property \Illuminate\Filesystem\Filesystem $files
 * @property \PayPal\Ipn\Listener $paypal
 * @property \Guzzle\Http\Client $guzzle
 * @property \IpnForwarder\UrlCollection $listeners
 * @property \Monolog\Logger $log
 * @property \PayPal\Ipn\Verifier $verifier
 * @property array|\IpnForwarder\IpnSubscriber[] $subscribers
 * @property \Illuminate\Http\Request request
 */
class App extends Container {
	/** @var  App */
	protected static $instance;
	/**
	 * @var
	 */
	private $name;


	public function __construct($name, $path, $logFile = 'logs/ipn-forwarder.log')
	{
		$this->name = $name;
		$this->instance('path', rtrim($path, DIRECTORY_SEPARATOR));
		$this->instance('log_file', $path . DIRECTORY_SEPARATOR . $logFile);
		$this->instance('subscribers', []);
	}


	public function boot()
	{
		$this->paypal->setVerifier($this->verifier);
	}

	public function addSubscriber(IpnSubscriber $subscriber)
	{
		$this->subscribers[] = $subscriber;
	}

	public function run()
	{
		$ipn = $this->getIpnResponse();
		$this->paypal->listen(function () use ($ipn)
		{
			if ($urls = $this->listeners->findListeners($ipn->invoice))
			{
				foreach ($this->subscribers as $subscriber)
					$subscriber->onValidIpn($ipn);
				$this->notifyListeners($ipn, $urls, $this->listeners->getMatchedParts());
				$this->log->addInfo('Notified ' . count($urls) . ' urls.', [$ipn->invoice, $this->request->url()]);
			}
			else
			{
				$this->log->addDebug('No listener was found', [$ipn->invoice, $this->request->url()]);
			}
		}, function () use ($ipn)
		{
			$this->log->addError('IPN is invalid', [$ipn->invoice, $this->request->url()]);
			foreach ($this->subscribers as $subscriber)
				$subscriber->onInvalidIpn($ipn);
		});
	}

	public function shutdown()
	{
		exit();
	}

	protected function notifyMany($urls)
	{
		$this->assertAppName();
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

	protected function assertAppName()
	{
		if (empty($this->name))
		{
			throw new Exception('Please set the application\'s name.');
		}
	}

	/**
	 * @param $urls
	 */
	public function notifyListeners(IPN $ipn, array $urls, array $parts = [])
	{
		$this->assertAppName();
		$requestData = [
			'service' => $this->getName(),
			'source' => $this->request->url(),
			'request' => [
				'url' => $this->request->url(),
				'method' => $this->request->method(),
				'headers' => $this->request->headers->all()
			],
			'matches' => $parts,
			'ipn' => $ipn->toArray(),
			'timestamp' => time()
		];
		$requests = $this->makeGuzzleRequests($urls, $requestData);
		$this->guzzle->send($requests);
	}

	/**
	 * @return IPN
	 */
	public function getIpnResponse()
	{
		$msg = Message::createFromGlobals();
		$this->verifier->setIpnMessage($msg);
		return new IPN($msg);
	}

	/**
	 * @param array $urls
	 * @param array $ipnData
	 * @return array|\Guzzle\Http\Message\RequestInterface[]
	 */
	public function makeGuzzleRequests(array $urls, array $ipnData)
	{
		$requests = [];
		$requestBody = ['json' => $ipnData];
		foreach ($urls as $url)
		{
			$requests[] = $this->guzzle->post($url, $requestBody);
		}
		return $requests;
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

	public function __get($name)
	{
		return $this->make($name);
	}


	/**
	 * @return App
	 */
	public static function getInstance()
	{
		return self::$instance;
	}

	/**
	 * @param App $instance
	 */
	public static function setInstance(App $instance)
	{
		self::$instance = $instance;
	}
}