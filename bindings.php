<?php
$app->bindService('paypal', 'PayPal\Ipn\Listener');
$app->bindService('guzzle', 'Guzzle\Http\Client');
$app->bindService('files', 'Illuminate\Filesystem\Filesystem');
$app->bindService('listeners', IpnForwarder\UrlCollection::class);
$app->bindService('log', 'Monolog\Logger', function ($app)
{
	$log = new Monolog\Logger($app->getName());
	$log->pushHandler(new Monolog\Handler\StreamHandler($app['log_file'], Monolog\Logger::WARNING));
	return $log;
});

$app->bindService('session.store', SessionHandlerInterface::class, function ($app)
{
	return new Illuminate\Session\FileSessionHandler($app['files'], $app['path']);
});

$app->bindService('session', Illuminate\Session\SessionInterface::class, function ($app)
{
	return new Illuminate\Session\Store($app->getName() . '_session', $app['session.store']);
});
$app->bindService('request', Illuminate\Http\Request::class, function ($app)
{
	return new Illuminate\Http\Request();
});
$app->resolving(Illuminate\Http\Request::class, function ($request, $app)
{
	$request->setSession($app['session']);
	return $request;
});
$app->bindService('verifier', PayPal\Ipn\Verifier::class, function ()
{
	return new PayPal\Ipn\Verifier\CurlVerifier();
});