<?php
/**
 * Component to provide an easy interface to basic Payone-API.
 *
 * @package			Payone
 * @subpackage		components
 * @version			0.2
 * @link			https://www.payone.de/
 * @author			Created by Marcus Spiegel on 2010-02-08. Last Editor: $Author$
 * @license			http://creativecommons.org/licenses/by-sa/3.0/
 * 
 * **Usage-Example (On-Off-Payment):**
 * {{{
 * // 1) set CC
 * $this->Payone->setCC(
 *	   '4111111111111111',
 *	   'V',
 *	   1002, // for 2010-02
 *	   '123'
 * );
 * 
 * // 2) add a person as address
 * $this->Payone->setPerson(array(
 *	   'company'   => 'My Company', 
 *	   'firstname' => 'Marcus',
 *	   'lastname'  => 'Spiegel'
 *	   'street'	   => 'Some Street 20a'
 *	   'country'   => 'DE', 
 *	   'language'  => 'en',
 *	   'zip'	   => '10123', 
 *	   'city'	   => 'Berlin', 
 *	   'email'	   => <enter E-Mail here>,
 * ));
 * 
 * // 3) setup the invoice
 * $this->Payone->setInvoice('RE-0001');
 * 
 * // 4) add an article
 * $this->Payone->addArticle($articleNr, $singleprice, $articleDescription, $amount, $vat);
 * 
 * // 5) authorize the payment, which processes the payment on success. Returns error on failure
 * $this->Payone->authorization('RE-0001', $overallprice, $currency, $sometext, 'cc');
 * }}}
 * 
 * **Usage-Example (Subscription):**
 * {{{
 * // 1) set CC
 * $this->Payone->setCC(
 *	   '4111111111111111',
 *	   'V',
 *	   1002, // for 2010-02
 *	   '123'
 * );
 * 
 * // 2) add a person as address
 * $this->Payone->setPerson(array(
 *	   'company'   => 'My Company', 
 *	   'firstname' => 'Marcus',
 *	   'lastname'  => 'Spiegel'
 *	   'street'	   => 'Some Street 20a'
 *	   'country'   => 'DE', 
 *	   'language'  => 'en',
 *	   'zip'	   => '10123', 
 *	   'city'	   => 'Berlin', 
 *	   'email'	   => <enter E-Mail here>,
 * ));
 * 
 * // 3) setup the product
 * $this->Payone->setProduct(<PRODUCTID>);
 * 
 * // 4) 
 * $this->Payone->createaccess(<PORTALID>, 'RE-0001');
 * }}}
 * 
 * To handle recurring payment workflows in different controllers, be advised 
 * to setup another PaymentComponent handling these instead of invoking payone 
 * methods directly from inside a controller.
 */
class PayoneComponent extends Component {
/**
 * The name of that component.
 *
 * @var string
 */
	var $name = "Payone";
	
/**
 * This should only be used in case of problems. Careful: CC-Data my be logged !!!!
 *
 * @var string
 */
	private $debug = false;

/**
 * This is the unique Merchant-ID as provided by Payone, see "Zahlungsportale / URLs"
 *
 * @var string
 */
	private $mid = null;

/**
 * This is the portal Id as configured in "Zahlungsportale".
 *
 * @var string
 */
	private $portalid = null;

/**
 * This is the Account-Id as configured in "Sub-Accounts"
 *
 * @var string
 */
	private $aid = null;
	
/**
 * Payone provides a test-mode for time of implementation
 *
 * @var string
 */
	private $mode = null;

/**
 * This is a 'secret' key configured in "Zahlungsportale / Erweitert"
 *
 * @var string
 */
	private $portal_key = null;
	
/**
 * This is the URL all requests are posted to, see "Zahlungsportale / URLs"
 *
 * @var string
 */
	private $api_url = "https://api.pay1.de/post-gateway/";
	
/**
 * Holds CreditCard-Data.
 *
 * @var string
 */
	private $cc = null;
	
/**
 * Holds personal data
 *
 * @var string
 */
	private $person = null;
	
/**
 * Holds article data
 *
 * @var string
 */
	private $article = array();
	
/**
 * Holds Invoice meta data
 *
 * @var string
 */
	private $invoice = null;
	
/**
 * Holds the productId of a subscription package
 *
 * @var string
 */
	private $productid = null;
	
/**
 * Holds the repsonse from payone api
 *
 * @var string
 */
	public $response = null;
	
/**
 * The language of the customer (ie.: for error messages). Default: en
 *
 * @var string
 */
	public $language = 'en';
	
/**
 * Encoding for all messages.Default: UTF-8
 *
 * @var string
 */
	public $encoding = 'UTF-8';
	
/**
 * Countries for accepted payments.
 *
 * @var string
 */
	public $countries = array();
	
/**
 * These are all required parameters for payment gateway.
 *
 * @var string
 */
	private $required = array(
		'mid', 'portalid', 'aid',
		'portal_key', 'api_url', 'language',
	);

/**
 * Collects and validates configuration. dies() on any error.
 *
 * @author Marcus Spiegel
 */
	public function initialize($controller, $settings = array()){ 
		$this->controller = $controller; 
		
		// load defaults
	    Configure::load('payone');
		$defaults = Configure::read('payone');
		if(empty($defaults)){
			die("Please setup payone config in config/payone");
		}else{
			foreach($defaults as $k => $v){
				$this->{$k} = $v;
			}
		}
		
		// now check requirements
		foreach($this->required as $r){
			if(empty($this->{$r})){
				die('Required config of "'.$r.'" is missing!');
			}
		}

		// might get translated in locales... sometimes, that's why countries 
		// get initialized.
		$this->countries = array(
			'DE'=>"Germany",
			'AT'=>"Austria",
			'CH'=>"Switzerland",
			'AF'=>"Afghanistan",
			'AL'=>"Albania",
			'DZ'=>"Algeria",
			'AS'=>"American Samoa",
			'AD'=>"Andorra",
			'AO'=>"Angola",
			'AI'=>"Anguilla",
			'AQ'=>"Antarctica",
			'AG'=>"Antigua and Barbuda",
			'AR'=>"Argentina",
			'AM'=>"Armenia",
			'AW'=>"Aruba",
			'AU'=>"Australia",
			'AX'=>"Aland Islands",
			'AZ'=>"Azerbaijan",
			'BS'=>"Bahamas",
			'BH'=>"Bahrain",
			'BD'=>"Bangladesh",
			'BB'=>"Barbados",
			'BL'=>"Saint Barthlemy",
			'BY'=>"Belarus",
			'BE'=>"Belgium",
			'BZ'=>"Belize",
			'BJ'=>"Benin",
			'BM'=>"Bermuda",
			'BT'=>"Bhutan",
			'BO'=>"Bolivia",
			'BA'=>"Bosnia and Herzegovina",
			'BW'=>"Botswana",
			'BV'=>"Bouvet Island",
			'BR'=>"Brazil",
			'IO'=>"British Indian Ocean Territory",
			'BN'=>"Brunei Darussalam",
			'BG'=>"Bulgaria",
			'BF'=>"Burkina Faso",
			'BI'=>"Burundi",
			'KH'=>"Cambodia",
			'CM'=>"Cameroon",
			'CA'=>"Canada",
			'CV'=>"Cape Verde",
			'KY'=>"Cayman Islands",
			'CF'=>"Central African Republic",
			'TD'=>"Chad",
			'CL'=>"Chile",
			'CN'=>"China",
			'CX'=>"Christmas Island",
			'CC'=>"Cocos (Keeling) Islands",
			'CO'=>"Colombia",
			'KM'=>"Comoros",
			'CG'=>"Congo",
			'CD'=>"Congo, the Democratic Republic of the Congo",
			'CK'=>"Cook Islands",
			'CR'=>"Costa Rica",
			'CI'=>"Côte d\'Ivoire",
			'HR'=>"Croatia",
			'CU'=>"Cuba",
			'CY'=>"Cyprus",
			'CZ'=>"Czech Republic",
			'DK'=>"Denmark",
			'DJ'=>"Djibouti",
			'DM'=>"Dominica",
			'DO'=>"Dominican Republic",
			'EC'=>"Ecuador",
			'EG'=>"Egypt",
			'SV'=>"El salvador",
			'GQ'=>"Equatorial Guinea",
			'ER'=>"Eritrea",
			'EE'=>"Estonia",
			'ET'=>"Ethiopia",
			'FK'=>"Falkland Islands",
			'FO'=>"Faroe Islands",
			'FJ'=>"Fiji",
			'FI'=>"Finland",
			'FR'=>"France",
			'GF'=>"French Guiana",
			'PF'=>"French Polynesia",
			'TF'=>"French Southern Territories",
			'GA'=>"Gabon",
			'GM'=>"Gambia",
			'GE'=>"Georgia",
			'GH'=>"Ghana",
			'GI'=>"Gibraltar",
			'GB'=>"United Kingdom",
			'GR'=>"Greece",
			'GL'=>"Greenland",
			'GD'=>"Grenada",
			'GP'=>"Guadeloupe",
			'GU'=>"Guam",
			'GT'=>"Guatemala",
			'GG'=>"Guernsey",
			'GN'=>"Guinea",
			'GW'=>"Guinea-Bissau",
			'GY'=>"Guyana",
			'HT'=>"Haiti",
			'HM'=>"Heard Island and McDonald Islands",
			'HN'=>"Honduras",
			'HK'=>"Hong Kong",
			'HU'=>"Hungary",
			'IS'=>"Iceland",
			'IN'=>"India",
			'ID'=>"Indonesia",
			'IR'=>"Iran",
			'IQ'=>"Iraq",
			'IE'=>"Ireland",
			'IM'=>"Isle of Man",
			'IL'=>"Israel",
			'IT'=>"Italy",
			'JM'=>"Jamaica",
			'JP'=>"Japan",
			'JE'=>"Jersey",
			'JO'=>"Jordan",
			'KZ'=>"Kazakstan",
			'KE'=>"Kenya",
			'KI'=>"Kiribati",
			'KP'=>"North Korea",
			'KR'=>"South Korea",
			'KW'=>"Kuwait",
			'KG'=>"Kyrgystan",
			'LA'=>"Lao",
			'LV'=>"Latvia",
			'LB'=>"Lebanon",
			'LS'=>"Lesotho",
			'LR'=>"Liberia",
			'LY'=>"Libyan Arab Jamahiriya",
			'LI'=>"Liechtenstein",
			'LT'=>"Lithuania",
			'LU'=>"Luxembourg",
			'MO'=>"Macau",
			'MK'=>"Macedonia (FYR)",
			'MF'=>"Saint Martin",
			'MG'=>"Madagascar",
			'MW'=>"Malawi",
			'MY'=>"Malaysia",
			'MV'=>"Maldives",
			'ML'=>"Mali",
			'MT'=>"Malta",
			'MH'=>"Marshall Islands",
			'MQ'=>"Martinique",
			'MR'=>"Mauritania",
			'MU'=>"Mauritius",
			'YT'=>"Mayotte",
			'MX'=>"Mexico",
			'FM'=>"Micronesia",
			'MD'=>"Moldova",
			'MC'=>"Monaco",
			'MN'=>"Mongolia",
			'ME'=>"Montenegro",
			'MS'=>"Montserrat",
			'MA'=>"Morocco",
			'MZ'=>"Mozambique",
			'MM'=>"Myanmar",
			'NA'=>"Namibia",
			'NR'=>"Nauru",
			'NP'=>"Nepal",
			'AN'=>"Netherlands Antilles",
			'NL'=>"Netherlands",
			'NC'=>"New Caledonia",
			'NZ'=>"New Zealand",
			'NI'=>"Nicaragua",
			'NE'=>"Niger",
			'NG'=>"Nigeria",
			'NU'=>"Niue",
			'NF'=>"Norfolk Island",
			'MP'=>"Northern Mariana Islands",
			'NO'=>"Norway",
			'OM'=>"Oman",
			'PK'=>"Pakistan",
			'PW'=>"Palau",
			'PS'=>"Palestinian Territory",
			'PA'=>"Panama",
			'PG'=>"Papua New Guinea",
			'PY'=>"Paraguay",
			'PE'=>"Peru",
			'PH'=>"Philippines",
			'PN'=>"Pitcairn",
			'PL'=>"Poland",
			'PT'=>"Portugal",
			'PR'=>"Puerto Rico",
			'QA'=>"Qatar",
			'RE'=>"Reunion",
			'RO'=>"Romania",
			'RU'=>"Russian Federation",
			'RW'=>"Rwanda",
			'GS'=>"South Georgia",
			'KN'=>"Saint Kitts and Nevis",
			'LC'=>"Saint Lucia",
			'VC'=>"Saint Vincent and the Grenadines",
			'WS'=>"Samoa",
			'SM'=>"San Marino",
			'ST'=>"Sao Tome and Principe",
			'SA'=>"Saudi Arabia",
			'SN'=>"Senegal",
			'RS'=>"Serbia",
			'SC'=>"Seychelles",
			'SL'=>"Sierra Leone",
			'SG'=>"Singapore",
			'SK'=>"Slovakia",
			'SI'=>"Slovenia",
			'SB'=>"Solomon Islands",
			'SO'=>"Somalia",
			'ZA'=>"South Africa",
			'ES'=>"Spain",
			'LK'=>"Sri Lanka",
			'SH'=>"Saint Helena",
			'PM'=>"Saint Pierre and Miquelon",
			'SD'=>"Sudan",
			'SR'=>"Suriname",
			'SJ'=>"Svalbard and Jan Mayen Islands",
			'SZ'=>"Swaziland",
			'SE'=>"Sweden",
			'SY'=>"Syria",
			'TW'=>"Taiwan",
			'TJ'=>"Tajikistan",
			'TZ'=>"Tanzania",
			'TH'=>"Thailand",
			'TL'=>"Timor-leste",
			'TG'=>"Togo",
			'TK'=>"Tokelau",
			'TO'=>"Tonga",
			'TT'=>"Trinidad and Tobago",
			'TN'=>"Tunisia",
			'TR'=>"Turkey",
			'TM'=>"Turkmenistan",
			'TC'=>"Turks and Caicos Islands",
			'TV'=>"Tuvalu",
			'UG'=>"Uganda",
			'UA'=>"Ukraine",
			'AE'=>"United Arab Emirates",
			'UM'=>"United States Minor Outlying Islands",
			'US'=>"United States",
			'UY'=>"Uruguay",
			'UZ'=>"Uzbekistan",
			'VU'=>"Vanuatu",
			'VA'=>"Holy See (Vatican City State)",
			'VE'=>"Venezuela",
			'VN'=>"Viet Nam",
			'VG'=>"Virgin Islands (British)",
			'VI'=>"Virgin Islands (U.S.)",
			'WF'=>"Wallis and Futuna Islands",
			'EH'=>"Western Sahara",
			'YE'=>"Yemen",
			'ZM'=>"Zambia",
			'ZW'=>"Zimbabwe",
			);
		parent::initialize($controller); 
}

/**
 * Switches to test mode. This is useful when testing transaction.
 * Payone provides a test Card-Number: 4111111111111111 (Visa), any date, any sec code.
 *
 * @return void
 * @author Marcus Spiegel
 */
	function setTest(){
		$this->mode = 'test';
	}
	
/**
 * Switches to live transactions. Test data won't be accepted any more and all real
 * Payments get collected.
 *
 * @return void
 * @author Marcus Spiegel
 */
	function setLive(){
		$this->mode = 'live';
	}
	
/**
 * Switches debugging to ON.
 *
 * @return void
 * @author Marcus Spiegel
 */
	function setDebug(){
		$this->debug = true;
	}
	
/**
 * Sets Creditcard data for later processing.
 *
 * @param string $cardpan ie. 4111111111111111
 * @param string $cardtype ie. V for VISA
 * @param string $cardexpiredate  ie. 1112
 * @param string $cardcvc2 ie. 123
 * @return object
 * @author Marcus Spiegel
 */
	function setCC($cardpan, $cardtype, $cardexpiredate, $cardcvc2){
		$this->cc = compact('cardpan', 'cardtype', 'cardexpiredate', 'cardcvc2');
		return $this->cc;
	}
	
/**
 * Adds personal date for CC-Validation and Invoice. Thus at least 'country', 
 * 'firstname' and 'lastname' have to be provided. Please refer to the Payone
 * API-Docs for a complete list of person data. 
 *
 * @param array $d 
 * @return void
 * @author Marcus Spiegel
 */
	function setPerson($d){
		if(
			!empty($d['country'])
			&& !empty($d['firstname'])
			&& !empty($d['lastname'])
		){
			$this->person = $d;
		}
	}
	
/**
 * Adds article data to an invoice.
 *
 * @param string $id article number,
 * @param string $pr price of a single unit (in it's lowest unit, ie. cent)
 * @param string $de a description
 * @param string $no number of units
 * @param string $va vat in %
 * @return void
 * @author Marcus Spiegel
 */
	function addArticle($id, $pr, $de, $no=1, $va=null){
		$i = count($this->article);
		$i++;
		$params = compact('id', 'pr', 'de', 'no', 'va');
		foreach($params as $k => $v){
			$this->article[$k][$i] = $v;
		}
	}
	
/**
 * Setup of an invoice
 *
 * @param string $invoiceid an invoice Id
 * @param string $invoiceappendix appendix text that may appear on your invoice
 * @param string $invoice_deliverymode method of invoice delivery M: Mail P: PDF (via E-Mail)
 * @return void
 * @author Marcus Spiegel
 */
	function setInvoice($invoiceid, $invoiceappendix=null, $invoice_deliverymode=null){
		if(!empty($invoiceid)){
            $this->invoice = compact('invoiceid', 'invoiceappendix', 'invoice_deliverymode');
		}
	}

/**
 * Setup of a product for subscription payments.
 *
 * @param string $productid the id of a product as setup in the payone manager.
 * @return void
 * @author Marcus Spiegel
 */	   
	function setProduct($productid){
		if(!empty($productid)){
			$this->productid = compact('productid');
		}
	}

/**
 * Validates Creditcard data based on prior setCC().
 *
 * @return boolean true on success
 * @author Marcus Spiegel
 */
	function creditcardcheck(){
		$request = am($this->cc, array('request' => 'creditcardcheck'));
		$this->response = $this->sendRequest($request);
		if($this->response['status'] == 'VALID'){
			return true;
		}
		return false;
	}
	
/**
 * Initiates a payment process. Payone will perform different actions 
 * depending on the clearingtype. Creditcard data gets validated before any
 * processing.
 *
 * @param string $reference ie. an invoice number
 * @param string $amount the amount total that has to be collected
 * @param string $currency the currency as supported by payone. Default: 'USD' 
 * @param string $narrative_text an optional text
 * @param string $clearingtype the clearingtype. Currently only 'cc' is supported.
 * @return void
 * @author Marcus Spiegel
 */
	function authorization($reference, $amount, $currency="USD", $narrative_text="", $clearingtype='cc'){
		$request = array(
			'request'		 => 'authorization',
			'reference'		 => $reference,
			'amount'		 => $amount,
			'currency'		 => $currency, 
			'narrative_text' => $narrative_text, 
			'clearingtype'	 => $clearingtype, 

		);
		switch($clearingtype){
			case 'cc' :
				if(!empty($this->cc)){
					$request = am($this->cc, $request);
				}
				
				if(!empty($this->person)){
					$request = am($this->person, $request);
				}

				if(!empty($this->invoice)){
					$request = am($this->invoice, $request);
				}
				
				if(!empty($this->article)){
					$request = am($this->article, $request);
				}

				$this->response = $this->sendRequest($request);
				break;
		}
		
		if($this->response['status'] != 'ERROR'){
			return true;
		}
		
		return false;
	}

/**
 * Initiate an access based subscription contract.
 *
 * @param string $portalid Id of the portal, as of payone manager
 * @param string $reference a reference for your internal tracking
 * @param string $clearingtype the clearingtype. Currently only 'cc' is supported.
 * @return void
 * @author Marcus Spiegel
 */	   
	function createaccess($portalid=null, $reference='', $clearingtype='cc'){
		$request = array(
			'request'		 => 'createaccess',
			'clearingtype'	 => $clearingtype,
			'reference'		 => $reference,
		);
		
		$this->portalid = $portalid;
		
		switch($clearingtype){
			case 'cc' :
				if(!empty($this->cc)){
					$request = am($this->cc, $request);
				}
			
				if(!empty($this->person)){
					$request = am($this->person, $request);
				}

				if(!empty($this->productid)){
					$request = am($this->productid, $request);
				}
				
				$this->response = $this->sendRequest($request);
				break;
		}
	
		if($this->response['status'] != 'ERROR'){
			return true;
		}
	
		return false;
	}

/**
 * API for logging events.
 *
 * @param string $msg Log message
 * @param integer $type Error type constant. Defined in app/config/core.php.
 * @return boolean Success of log write
 * @access public
 */
	function log($msg, $type = 'payone') {
		return parent::log($msg, $type);
	}

/**
 * Convenient method to get a better coverage in unit testing :)
 *
 * @return void
 * @author Marcus Spiegel
 */	   
	function testerrors(){
		$this->api_url = '';
		return $this->sendRequest();
	}
	
/**
 * low level request method. Get's called by any method depending on it's purpose.
 *
 * @param array $request_array 
 * @return string
 * @author Marcus Spiegel
 */
	private function sendRequest($request = array()){

		$request['portalid'] = $this->portalid;
		$request['mid']		 = $this->mid;
		$request['aid']		 = $this->aid;
		$request['mode']	 = $this->mode;
		$request['encoding'] = $this->encoding;
		$request['key']		 = md5($this->portal_key);
		
		// build a query
		$query = http_build_query($request);
		$ch	   = curl_init($this->api_url);
		
		if(!empty($request['request'])){
			if($this->debug){
				$this->log('REQUEST: '.$request['request']);
			}
		}else{
			$this->log('REQUEST: PARAMETER MISSING!');
		}
		
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_TIMEOUT, 45);

		$result = curl_exec($ch);
		
		if($this->debug){
			$this->log($query);
			$this->log($result);
		}
		
		if (curl_error($ch)) {
			$response[]="errormessage=".curl_errno($ch).": ".curl_error($ch);
			$response[]="this is a critical connection error!";
		}else{
			$response=explode("\n",$result);
		}
		curl_close($ch);

		if(is_array($response)){
			foreach($response as $linenum => $line){
				$pos=strpos($line,"=");
				if($pos > 0) {
					$output[substr($line,0,$pos)]=trim(substr($line,$pos+1));
				}elseif(strlen($line) > 0) {
					$output[$linenum]=$line;
				}
			}
		}

		return $output;
	}
   
}
?>