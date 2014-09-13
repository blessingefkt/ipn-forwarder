<?php
$this->bindService('log', 'Monolog\Logger', function ($this)
{
	$log = new Monolog\Logger($this->getName());
	$log->pushHandler(new Monolog\Handler\StreamHandler($this['log_file'], Monolog\Logger::WARNING));
	return $log;
});
$this->bindService('request', Illuminate\Http\Request::class, function ($this)
{
	return Illuminate\Http\Request::createFromGlobals();
});
$this->bindService('guzzle', GuzzleHttp\Client::class, function ($app)
{
	$client = new GuzzleHttp\Client();
	$client->getEmitter()->attach($app->make(IpnForwarder\Guzzle\GuzzleSubscriber::class));
	return $client;
});
$this->bindService('paypal', PayPal\Ipn\Listener::class);
$this->bindService('listeners', IpnForwarder\UrlCollection::class);
$this->bindService('ipnForwarder', IpnForwarder\Forwarder::class);
$this->bindService('ipnProcessor', IpnForwarder\Processor::class);