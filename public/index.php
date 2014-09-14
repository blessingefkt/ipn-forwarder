<?php

ini_set('max_execution_time', 0);
date_default_timezone_set('UTC');

$composer = require __DIR__ . '/../vendor/autoload.php';

$app = new IpnForwarder\App('my-ipn-forwarder', __DIR__ . '/..');
$app->boot();


/** @var IpnForwarder\Receiver $receiver */
$receiver = $app->make(IpnForwarder\Receiver::class);

$receiver->setLogger($app->log);
$receiver->processor()->setVerifier(new PayPal\Ipn\Verifier\CurlVerifier())
	->skipVerification()
	->setSandbox();
$receiver->forwarder()->setKey($app->getName());

$formatter = new IpnForwarder\Format\SimpleFormatter();
$formatter->setRequest($app->request);
$receiver->forwarder()->setFormatter($formatter);

$receiver->processor()->getUrlCollection()->addListener(".*", [
	'http://'
]);

try
{
	$message = IpnForwarder\Receiver::makeMessage($app->request->query());
	$response = $receiver->listen($message);
	$app->makeResponse($response)->send();
} catch (\Exception $ex)
{
	$app->logException($ex);
	$response = $app->makeErrorResponse($ex->getMessage(), $ex->getCode());
	$response->send();
}

$app->shutdown();