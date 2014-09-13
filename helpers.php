<?php
/**
 * @return IpnForwarder\App
 */
function app()
{
	return IpnForwarder\App::getInstance();
}

/**
 * @param $app
 * @throws \Illuminate\Filesystem\FileNotFoundException
 */
function loadListenersFromFile($path)
{
	$sites = app()->files->getRequire($path);
	if (isset($sites['global']))
	{
		foreach ($sites as $site)
		{
			app()->listeners->addGlobalListener($site);
		}
	}
	if (isset($sites['invoice_patterns']))
	{

		foreach ($sites['invoice_patterns'] as $pattern => $urls)
		{
			if (is_string($urls))  $urls = [$urls];
			app()->listeners->addListenerGroup($pattern, $urls);
		}
	}
}