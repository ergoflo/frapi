<?php
/**
 * @author Echolibre ltd. 2009 <freedom@echolibre.com> 
 * @copyright Echolibre ltd. 2009
 */
class Lupin_Payment_PayPal_Standard
{
    const DEV_API_ENDPOINT  = 'https://www.sandbox.paypal.com/cgi-bin/webscr?';
    const PROD_API_ENDPOINT = 'https://www.paypal.com/cgi-bin/webscr?';
    
    /**
     * The API Username supplied by paypal
     *
     * @var string $_username The API username.
     */
    protected $_username;
    
    /**
     * The API Password supplied by paypal
     *
     * @var string $_password The API Password.
     */
    protected $_password;
    
    /**
     * The API Signature supplied by paypal
     */
    protected $_signature;
    
    /**
     * The type of request. Development or Production? (dev or prod)
     *
     * @var string $__type  The type of request
     */
    private   $__type = 'prod';
    
    /**
     * The response from the server.
     *
     * @var string $_response The response.
     */
    protected $_response;
    
    /**
     * The required parameters in order to proceed and
     * complete a payment using the Paypal Pro API
     *
     * @var array $_requiredParameters The list of required parameters.
     */
    protected $_requiredParameters = array(
        //'USER'           => '',
        //'PWD'            => '',
        //'SIGNATURE'      => '',
        //'VERSION'        => '56.0',
        'cmd'            => '_xclick',
        'business'       => 'hello@vidcollege.com',
        'item_name'      => 'TopUp',
        'item_number'    => 'VidSchool Topup',
        'amount'         => '',
        'currency_code'  => 'USD',
        'quantity'       => '1',
        'notify_url'         => 'http://vidschool.com/profile/pay/confirm',
        'bn'             => 'ButtonFactory.PayPal.001',
        'custom'         => '',
        'no_shipping'    => '1',
        'no_note'        => '1',
        
    );
    
    /**
     * The object constructor
     *
     * This is the Lupin_Payment_PayPal_Pro constructor.
     * It is used to set the username, password, signature and type
     * of the object we are about to use to make the payment.
     *
     * @param string $username  The API username
     * @param string $password  The API Password
     * @param string $signature The API signature
     * @param string $type      The environment type ('dev' or 'prod')
     */
    public function __construct($username, $password, $signature, $type = 'prod')
    {
        $this->_username  = $username;
        $this->_password  = $password;
        $this->_signature = $signature;
        
        $this->__type = $type;
        
        //$this->_requiredParameters['USER']      = $this->_username;
        //$this->_requiredParameters['PWD']       = $this->_password;
        //$this->_requiredParameters['SIGNATURE'] = $this->_signature;
    }

    /**
     * Get a list of required parameters
     *
     * This method returns a list of required parameters in order to request
     * the payPal pro API.
     *
     * @return array $this->_requiredParameters The list of required parameters
     */
    public function getRequiredParameters()
    {
        return $this->_requiredParameters;
    }
    
    /**
     * The magic set
     *
     * This method will set the values of the required parameters.
     *
     * @param  string $name  The array key in the required parameters list
     * @param  string $value The value of the array key in the required parameters list.
     * @return void
     */
    public function __set($name, $value)
    {
        $this->_requiredParameters[strtolower($name)] = $value;
    }
    
    /**
     * Send
     *
     * Make the request on the server using streams
     *
     *    <form method="post" action="https://www.paypal.com/cgi-bin/webscr" target="paypal">
     *     <input type="hidden" name="cmd" value="_xclick">
     *     <input type="hidden" name="business" value="hello@vidcollege.com">
     *     <input type="hidden" name="item_name" value="Testing">
     *     <input type="hidden" name="item_number" value="">
     *     <input type="hidden" name="amount" value="666">
     *     <input type="hidden" name="currency_code" value="USD">
     *     <input type="hidden" name="quantity" value="1">
     *     <input type="hidden" name="shipping" value="0">
     *     <input type="hidden" name="shipping2" value="0">
     *     <input type="hidden" name="bn"  value="ButtonFactory.PayPal.001">
     *     <input type="image" name="add" src="VidSchool">
     *    </form>
     *
     *
     * @return void
     */
    public function send() 
    {
        $opts = array(
            'http' => array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query($this->_requiredParameters)
            )
        );

        $context = stream_context_create($opts);

        $url = self::PROD_API_ENDPOINT;

        if ($this->__type == 'dev') {
            //$url = self::DEV_API_ENDPOINT;
        }

        $data = http_build_query($this->_requiredParameters);

        header("Location: $url$data");
        exit();        
        $this->_response = file_get_contents($url, false, $context);
    }
    
    /**
     * Validate a request
     *
     * This will validate that all the required parameters are set
     * and if not it returns the list of missing parameters
     *
     * @return mixed Either bool true or an array of the missing required
     *               parameters in order to validate.
     */
    public function validate()
    {
        $valid = true; 
        
        foreach ($this->_requiredParameters as $param => $value) {
            if (!isset($this->_requiredParameters[$param]) || $value == '') {
                $valid = false;
            }
        }
        
        if ($valid === false) {
            return $this->getMissingParameters();
        }
        
        return $valid;
    }
    
    /**
     * Get the server response
     *
     * This method returns the response of the server in a key/value pair.
     *
     * @return array the list of values returned.
     */
    public function getResponse()
    {
        $parts    = explode('&', $this->_response);
        $response = array();
        
        foreach ($parts as $part) {
            $innerPart = explode('=', $part);
            if (isset($innerPart[0]) && isset($innerPart[1])) {
                $response[$innerPart[0]] = urldecode($innerPart[1]);
            }
        }
        
        return $response;
    }
    
    /** 
     * Get missing parameters
     *
     * This returns a list of missing parameters.
     *
     * @return array the list of missing parameters.
     */
    public function getMissingParameters()
    {
        $missing = array();
        foreach ($this->_requiredParameters as $param => $value) {
            if ($value == '') {
                $missing[] = $param;
            }
        }
        
        return $missing;
    }
}
