<?php namespace IpnForwarder;

class UrlCollection {
	/** @var  array */
	private $forwardUrls = [];
	protected $globalListeners = [];
	private $matchedParts;

	/**
	 * @param array $urls
	 * @return $this
	 */
	public function setGlobalListeners(array $urls)
	{
		$this->globalListeners = $urls;
		return $this;
	}

	/**
	 * @param string|array $url
	 * @return $this
	 */
	public function addGlobalListener($url)
	{
		$urls = is_array($url) ? $url : func_get_args();
		$this->globalListeners = array_merge($this->globalListeners, $urls);
		return $this;
	}

	/**
	 * @param $invoicePattern
	 * @param string|array $url
	 * @return $this
	 */
	public function addListener($invoicePattern, $url)
	{
		$urls = is_array($url) ? $url : [$url];
		if (isset($this->forwardUrls[$invoicePattern]))
		{
			$this->forwardUrls[$invoicePattern] = array_merge($this->forwardUrls[$invoicePattern], $urls);
		}
		else
		{
			$this->forwardUrls[$invoicePattern] = $urls;
		}
		return $this;
	}

	/**
	 * @param $invoiceId
	 * @return array|bool
	 */
	public function findListeners($invoiceId)
	{
		$this->matchedParts = [];
		foreach ($this->forwardUrls as $invoicePattern => $listeners)
		{
			if ($this->patternMatches($invoicePattern, $invoiceId, $_parts))
			{
				$this->matchedParts = $_parts;
				return array_merge($this->globalListeners, $listeners);
			}
		}
		return false;
	}

	/**
	 * @return array
	 */
	public function getMatchedParts()
	{
		return $this->matchedParts;
	}

	/**
	 * @param $invoicePattern
	 * @param $invoiceId
	 * @param null $_parts
	 * @return int
	 */
	protected function patternMatches($invoicePattern, $invoiceId, &$_parts = null)
	{
		$pattern = sprintf('/%s/', $invoicePattern);
		$result = preg_match($pattern, $invoiceId, $_parts);
		array_shift($_parts);
		return $result == 1;
	}

} 