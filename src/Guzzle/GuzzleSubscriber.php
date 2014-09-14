<?php namespace IpnForwarder\Guzzle;


use GuzzleHttp\Event\EmitterInterface;
use GuzzleHttp\Event\ErrorEvent;
use GuzzleHttp\Event\SubscriberInterface;
use Monolog\Logger;

class GuzzleSubscriber implements SubscriberInterface {
	/**
	 * @var \Monolog\Logger
	 */
	private $log;

	public function __construct(Logger $log)
	{
		$this->log = $log;
	}

	/**
	 * Returns an array of event names this subscriber wants to listen to.
	 *
	 * The returned array keys MUST map to an event name. Each array value
	 * MUST be an array in which the first element is the name of a function
	 * on the EventSubscriber OR an array of arrays in the aforementioned
	 * format. The second element in the array is optional, and if specified,
	 * designates the event priority.
	 *
	 * For example, the following are all valid:
	 *
	 *  - ['eventName' => ['methodName']]
	 *  - ['eventName' => ['methodName', $priority]]
	 *  - ['eventName' => [['methodName'], ['otherMethod']]
	 *  - ['eventName' => [['methodName'], ['otherMethod', $priority]]
	 *  - ['eventName' => [['methodName', $priority], ['otherMethod', $priority]]
	 *
	 * @return array
	 */
	public function getEvents()
	{
		return [
			'error' => ['onError', \GuzzleHttp\Event\RequestEvents::EARLY]
		];
	}

	public function onError(ErrorEvent $event)
	{
		if ($event->getResponse())
		{
			$this->log->error('guzzle_error: ' . $event->getException()->getMessage());
		}
		else
		{
			$ex = $event->getException();
			$this->log->error($ex->getMessage() . ' -- ' . $ex->getTraceAsString(),
				[$ex->getCode(), $ex->getLine(), $ex->getFile()]);
		}
		$event->stopPropagation();
	}
}