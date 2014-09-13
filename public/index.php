<?php
ini_set('max_execution_time', 0);
date_default_timezone_set('UTC');

$composer = require __DIR__ . '/../vendor/autoload.php';

$app = new IpnForwarder\App('my-ipn-forwarder', __DIR__ . '/..');
$app->boot();

ini_set('display_errors', 'Off');

$app->ipnForwarder->setKey($app->getName());
$app->ipnProcessor->setVerifier(new PayPal\Ipn\Verifier\CurlVerifier())
	->skipVerification()
	->setSandbox();

$app->ipnProcessor->getUrlCollection()->addListener(".*", [
	'http://'
]);

try
{
	$app->run();
} catch (\Exception $ex)
{
	$app->logException($ex);
	$app->setErrorResponse($ex->getMessage(), $ex->getCode());
}

$app->getResponse()->send();

$app->shutdown();