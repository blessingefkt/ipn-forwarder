<?php namespace IpnForwarder;


use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use PayPal\Ipn\Message;

class IpnEntity implements Arrayable, Jsonable, \JsonSerializable {
	/** @var  string */
	protected $report;
	/** @var array */
	protected $invoiceForwardUrls = [];
	/** @var array */
	protected $invoiceIdMatches = [];
	/** @var string */
	public $address_city;
	/** @var string */
	public $address_country;
	/** @var string */
	public $address_country_code;
	/** @var string */
	public $address_name;
	/** @var string */
	public $address_state;
	/** @var string */
	public $address_status;
	/** @var string */
	public $address_street;
	/** @var string */
	public $address_zip;
	/** @var string */
	public $business;
	/** @var string */
	public $custom;
	/** @var string */
	public $first_name;
	/** @var string */
	public $invoice;
	/** @var string */
	public $item_name;
	/** @var string */
	public $item_number;
	/** @var string */
	public $last_name;
	/** @var string */
	public $mc_currency;
	/** @var double */
	public $mc_fee;
	/** @var double */
	public $mc_gross;
	/** @var double */
	public $mc_gross1;
	/** @var string */
	public $notify_version;
	/** @var string */
	public $payer_email;
	/** @var string */
	public $payer_id;
	/** @var string */
	public $payer_status;
	/** @var string */
	public $payment_date;
	/** @var string */
	public $payment_status;
	/** @var string */
	public $payment_type;
	/** @var int */
	public $quantity;
	/** @var string */
	public $receiver_email;
	/** @var string */
	public $receiver_id;
	/** @var double */
	public $shipping;
	/** @var double */
	public $tax;
	/** @var bool */
	public $test_ipn;
	/** @var string */
	public $txn_id;
	/** @var string */
	public $txn_type;
	/** @var string */
	public $verify_sign;
	/** @var string */
	public $residence_country;

	/**
	 * @param Message $message
	 * @param string $report
	 */
	public function __construct(Message $message, $report = null)
	{
		foreach ($message as $prop => $value)
		{
			$this->{$prop} = $value;
		}
		$this->report = $report;
	}

	public function toArray()
	{
		return [
			'address_city' => $this->address_city,
			'address_country' => $this->address_country,
			'address_country_code' => $this->address_country_code,
			'address_name' => $this->address_name,
			'address_state' => $this->address_state,
			'address_status' => $this->address_status,
			'address_street' => $this->address_street,
			'address_zip' => $this->address_zip,
			'business' => $this->business,
			'custom' => $this->custom,
			'first_name' => $this->first_name,
			'invoice' => $this->invoice,
			'item_name' => $this->item_name,
			'item_number' => $this->item_number,
			'last_name' => $this->last_name,
			'mc_currency' => $this->mc_currency,
			'mc_fee' => $this->mc_fee,
			'mc_gross' => $this->mc_gross,
			'mc_gross1' => $this->mc_gross1,
			'notify_version' => $this->notify_version,
			'payer_email' => $this->payer_email,
			'payer_id' => $this->payer_id,
			'payer_status' => $this->payer_status,
			'payment_status' => $this->payment_status,
			'payment_type' => $this->payment_type,
			'quantity' => $this->quantity,
			'receiver_email' => $this->receiver_email,
			'receiver_id' => $this->receiver_id,
			'shipping' => $this->shipping,
			'tax' => $this->tax,
			'test_ipn' => $this->test_ipn,
			'txn_id' => $this->txn_id,
			'txn_type' => $this->txn_type,
			'verify_sign' => $this->verify_sign,
			'residence_country' => $this->residence_country
		];
	}

	/**
	 * @param array $urls
	 * @return $this
	 */
	public function setForwardUrls(array $urls)
	{
		$this->invoiceForwardUrls = $urls;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getForwardUrls()
	{
		return $this->invoiceForwardUrls;
	}

	/**
	 * @param array $matches
	 * @return $this
	 */
	public function setInvoiceMatches(array $matches)
	{
		$this->invoiceIdMatches = $matches;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getInvoiceMatches()
	{
		return $this->invoiceIdMatches;
	}

	/**
	 * @return string
	 */
	public function getReport()
	{
		return $this->report;
	}

	/**
	 * Convert the object to its JSON representation.
	 *
	 * @param  int $options
	 * @return string
	 */
	public function toJson($options = 0)
	{
		return json_encode($this->jsonSerialize(), $options);
	}

	/**
	 * (PHP 5 &gt;= 5.4.0)<br/>
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}
}