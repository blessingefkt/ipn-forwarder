<?php namespace IpnForwarder;

interface IpnSubscriber {
	public function onValidIpn(IpnEntity $IPN);

	public function onInvalidIpn(IpnEntity $IPN);
} 