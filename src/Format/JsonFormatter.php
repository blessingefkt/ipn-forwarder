<?php namespace IpnForwarder\Format;

use IpnForwarder\IpnEntity;

interface JsonFormatter {
	public function formatJsonResponse(IpnEntity $ipn);
} 