<?php namespace IpnForwarder;

class UrlCollection {
	/** @var  array */
	private $listeners = [];
	protected $globalListeners = [];
	private $matchedParts;

	public function addGlobalListener($url)
	{
		$this->globalListeners[] = $url;
	}

	public function addListener($invoicePattern, $url)
	{
		$this->listeners[$invoicePattern][] = $url;
	}

	/**
	 * @param $invoiceId
	 * @return array|bool
	 */
	public function findListeners($invoiceId)
	{
		$this->matchedParts = [];
		foreach ($this->listeners as $invoicePattern => $listeners)
		{
			$_parts = [];
			if (preg_match("&{$invoicePattern}&", $invoiceId, $_parts))
			{
				$this->matchedParts = $_parts;
				return array_merge($this->globalListeners, $listeners);
			}
		}
		return false;
	}

	public function addListenerGroup($invoicePattern, array $urls)
	{
		if (isset($this->listeners[$invoicePattern]))
		{
			$this->listeners[$invoicePattern] = array_merge($this->listeners[$invoicePattern], $urls);
		}
		else
		{
			$this->listeners[$invoicePattern] = $urls;
		}
	}

	/**
	 * @return array
	 */
	public function getMatchedParts()
	{
		return $this->matchedParts;
	}

} 