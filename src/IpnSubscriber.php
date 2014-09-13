<?php namespace IpnForwarder;

interface IpnSubscriber {
	public function onValidIpn(IPN $IPN);

	public function onInvalidIpn(IPN $IPN);
} 