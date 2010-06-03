<?php
/**
 * Sample configuration options for the payone service.
 *
 * @package         Payone
 * @subpackage      config
 * @version         0.2
 * @author          Created by Marcus Spiegel on 2010-06-03. Last Editor: $Author$
 * @license		 	http://creativecommons.org/licenses/by-sa/3.0/
 */
$config['payone'] = array(

	/**
	 * Payone provides a test-mode for time of implementation
	 */
	'mode' => "test",

	/**
	 * This is the unique Merchant-ID as provided by Payone, see "Zahlungsportale / URLs"
	 */
	'mid' => 0,

	/**
	 * This is the portal Id as configured in "Zahlungsportale".
	 */
	'portalid' => 0,

	/**
	 * This is the Account-Id as configured in "Sub-Accounts"
	 */
	'aid' => 0,

	/**
	 * This is a 'secret' key configured in "Zahlungsportale / Erweitert"
	 */
	'portal_key' => "<put in your secret key>",
	
	/**
	 * This is the URL all requests are posted to, see "Zahlungsportale / URLs"
	 */
	'api_url'	=> "https://api.pay1.de/post-gateway/",
	
	/**
	 * The language of the customer (ie.: for error messages). Default: en
	 */
	'language' => 'en',
	
	/**
	 * Encoding for all messages.Default: UTF-8
	 */
	'encoding' => 'UTF-8',
	
	);	
?>