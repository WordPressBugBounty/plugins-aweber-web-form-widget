<?php
namespace AWeberWebFormPluginNamespace;

require_once('exceptions.php');
require_once('oauth_adapter.php');
require_once('oauth_application.php');
require_once('oauth2_application.php');
require_once('aweber_response.php');
require_once('aweber_collection.php');
require_once('aweber_entry_data_array.php');
require_once('aweber_entry.php');

/**
 * AWeberServiceProvider
 *
 * Provides specific AWeber information or implementing OAuth.
 * @uses OAuthServiceProvider
 * @package
 * @version $id$
 */
class AWeberServiceProvider implements OAuthServiceProvider {

    /**
     * @var String Location for API calls
     */
    public $baseUri = 'https://api.aweber.com/1.0';

    /**
     * @var String Location to request an access token
     */
    public $accessTokenUrl = 'https://auth.aweber.com/1.0/oauth/access_token';

    /**
     * @var String Location to authorize an Application
     */
    public $authorizeUrl = 'https://auth.aweber.com/1.0/oauth/authorize';

    /**
     * @var String Location to request a request token
     */
    public $requestTokenUrl = 'https://auth.aweber.com/1.0/oauth/request_token';


    public function getBaseUri() {
        return $this->baseUri;
    }

    public function removeBaseUri($url) {
        return str_replace($this->getBaseUri(), '', $url);
    }

    public function getAccessTokenUrl() {
        return $this->accessTokenUrl;
    }

    public function getAuthorizeUrl() {
        return $this->authorizeUrl;
    }

    public function getRequestTokenUrl() {
        return $this->requestTokenUrl;
    }

    public function getAuthTokenFromUrl() { return ''; }
    public function getUserData() { return ''; }

}

/**
 * AWeberAPIBase
 *
 * Base object that all AWeberAPI objects inherit from.  Allows specific pieces
 * of functionality to be shared across any object in the API, such as the
 * ability to introspect the collections map.
 *
 * @package
 * @version $id$
 */
class AWeberAPIBase {

    /**
     * Maintains data about what children collections a given object type
     * contains.
     */
    static protected $_collectionMap = array(
        'account'              => array('lists', 'integrations'),
        'broadcast_campaign'   => array('links', 'messages', 'stats'),
        'followup_campaign'    => array('links', 'messages', 'stats'),
        'link'                 => array('clicks'),
        'list'                 => array('campaigns', 'custom_fields', 'subscribers',
                                        'web_forms', 'web_form_split_tests'),
        'web_form'             => array(),
        'web_form_split_test'  => array('components'),
    );

    /**
     * loadFromUrl
     *
     * Creates an object, either collection or entry, based on the given
     * URL.
     *
     * @param mixed $url    URL for this request
     * @access public
     * @return AWeberEntry or AWeberCollection
     */
    public function loadFromUrl($url) {
        $data = $this->adapter->request('GET', $url);
        return $this->readResponse($data, $url);
    }

    protected function _cleanUrl($url) {
        return str_replace($this->adapter->app->getBaseUri(), '', $url);
    }

    /**
     * readResponse
     *
     * Interprets a response, and creates the appropriate object from it.
     * @param mixed $response   Data returned from a request to the AWeberAPI
     * @param mixed $url        URL that this data was requested from
     * @access protected
     * @return mixed
     */
    protected function readResponse($response, $url) {
        $this->adapter->parseAsError($response);
        if (!empty($response['id'])) {
            return new AWeberEntry($response, $url, $this->adapter);
        } else if (array_key_exists('entries', $response)) {
            return new AWeberCollection($response, $url, $this->adapter);
        }
        return false;
    }
}

/**
 * AWeberAPI
 *
 * Creates a connection to the AWeberAPI for a given consumer application.
 * This is generally the starting point for this library.  Instances can be
 * created directly with consumerKey and consumerSecret.
 * @uses AWeberAPIBase
 * @package
 * @version $id$
 */
class AWeberAPI extends AWeberAPIBase {

    /**
     * @var String Consumer Key
     */
    public $consumerKey    = false;

    /**
     * @var String Consumer Secret
     */
    public $consumerSecret = false;

    /**
     * @var Object - Populated in setAdapter()
     */
    public $adapter = false;

    /**
     * Uses the app's authorization code to fetch an access token
     *
     * @param String Authorization code from authorize app page
     */
    public static function getDataFromAweberID($string) {
        list($consumerKey, $consumerSecret, $requestToken, $tokenSecret, $verifier) = AWeberAPI::_parseAweberID($string);

        if (!$verifier) {
            return null;
        }
        $aweber = new AweberAPI($consumerKey, $consumerSecret);
        $aweber->adapter->user->requestToken = $requestToken;
        $aweber->adapter->user->tokenSecret = $tokenSecret;
        $aweber->adapter->user->verifier = $verifier;
        list($accessToken, $accessSecret) = $aweber->getAccessToken();
        return array($consumerKey, $consumerSecret, $accessToken, $accessSecret);
    }

    protected static function _parseAWeberID($string) {
        $values = explode('|', $string);
        if (count($values) < 5) {
            return null;
        }
        return array_slice($values, 0, 5);
    }

    /**
     * Sets the consumer key and secret for the API object.  The
     * key and secret are listed in the My Apps page in the labs.aweber.com
     * Control Panel OR, in the case of distributed apps, will be returned
     * from the getDataFromAweberID() function
     *
     * @param String Consumer Key
     * @param String Consumer Secret
     * @return null
     */
    public function __construct($key, $secret) {
        // Load key / secret
        $this->consumerKey    = $key;
        $this->consumerSecret = $secret;

        $this->setAdapter();
    }

    /**
     * Returns the authorize URL by appending the request
     * token to the end of the Authorize URI, if it exists
     *
     * @return string The Authorization URL
     */
    public function getAuthorizeUrl() {
        $requestToken = $this->user->requestToken;
        return (empty($requestToken)) ?
            $this->adapter->app->getAuthorizeUrl()
                :
            $this->adapter->app->getAuthorizeUrl() . "?oauth_token={$this->user->requestToken}";
    }

    /**
     * Sets the adapter for use with the API
     */
    public function setAdapter($adapter=null) {
        if (empty($adapter)) {
            $serviceProvider = new AWeberServiceProvider();
            $adapter = new OAuthApplication($serviceProvider);
            $adapter->consumerKey = $this->consumerKey;
            $adapter->consumerSecret = $this->consumerSecret;
        }
        $this->adapter = $adapter;
    }

    /**
     * Fetches account data for the associated account
     *
     * @param String Access Token (Only optional/cached if you called getAccessToken() earlier
     *      on the same page)
     * @param String Access Token Secret (Only optional/cached if you called getAccessToken() earlier
     *      on the same page)
     * @return Object AWeberCollection Object with the requested
     *     account data
     */
    public function getAccount($token=false, $secret=false) {
        if ($token && $secret) {
            $user = new OAuthUser();
            $user->accessToken = $token;
            $user->tokenSecret = $secret;
            $this->adapter->user = $user;
        }

        $body = $this->adapter->request('GET', '/accounts');
        $accounts = $this->readResponse($body, '/accounts');
        return $accounts[0];
    }

    /**
     * PHP Automagic
     */
    public function __get($item) {
        if ($item == 'user') return $this->adapter->user;
        trigger_error('Could not find "' . esc_html($item) . '"');
    }

    /**
     * Request a request token from AWeber and associate the
     * provided $callbackUrl with the new token
     * @param String The URL where users should be redirected
     *     once they authorize your app
     * @return Array Contains the request token as the first item
     *     and the request token secret as the second item of the array
     */
    public function getRequestToken($callbackUrl) {
        $requestToken = $this->adapter->getRequestToken($callbackUrl);
        return array($requestToken, $this->user->tokenSecret);
    }

    /**
     * Request an access token using the request tokens stored in the
     * current user object.  You would want to first set the request tokens
     * on the user before calling this function via:
     *
     *    $aweber->user->tokenSecret  = $_COOKIE['requestTokenSecret'];
     *    $aweber->user->requestToken = $_GET['oauth_token'];
     *    $aweber->user->verifier     = $_GET['oauth_verifier'];
     *
     * @return Array Contains the access token as the first item
     *     and the access token secret as the second item of the array
     */
    public function getAccessToken() {
        return $this->adapter->getAccessToken();
    }
}


/**
 * AWeberOAuth2API
 *
 * Creates a connection to the AWeberAPI for a given consumer application.
 * This is generally the starting point for this library.  Instances can be
 * created directly with consumerKey and consumerSecret.
 * @uses AWeberAPIBase
 * @package
 * @version $id$
 */
class AWeberOAuth2API extends AWeberAPIBase {

    // Option Name to store the OAuth2 tokens in Database.
    private $oauth2AuthorizedOptions = 'AWeberOAuth2AuthorizeOptions';

    public $adapter = False;

    public function __construct($accessToken=null, $refreshToken=null, $expiresOn=null) {
        // Create an OAuth2 application, to get connected with AWeber account and get access to it.
        $this->adapter = new OAuth2Application($accessToken, $refreshToken, $expiresOn);
    }

    /**
     * getRandomCodeVerifier
     *
     * Generated an random bytes and encode to base62
     *
     * @param  Access Token.
     * @access private
     * @return random bytes.
     */
    private function getRandomCodeVerifier() {
        $randomBytes = random_bytes(64);
        return rtrim(strtr(base64_encode($randomBytes), "+/", "-_"), "=");
    }

    /**
     * getAuthorizeUrl
     *
     * Generates the OAuth2 Authorization URL.
     *
     * @param  redirectURI, Its your callback URL that needs to be called, once the AWeber Completes the authentication.
     * @access public
     * @return OAuth2 authorized URL.
     */
    public function getAuthorizeUrl() {
        $authorizeOptions = array(
            'code_verifier' => $this->getRandomCodeVerifier()
        );
        // Store the state and code verifier.
        // These are required while getting the accees token
        update_option($this->oauth2AuthorizedOptions, $authorizeOptions);
        // Get the Authorize URL.
        return $this->adapter->getAuthorizeUrl($authorizeOptions['code_verifier']);
    }

    /**
     * getAccessToken
     *
     * authenticate with AWeber Account and generates the Access Token
     *
     * @param  State
     * @param  authorizedCode, received from the URL the user is redirected to
                after authorizing the integration to their account.
     * @access public
     * @return array
     */
    public function generateAccessToken($authorizedCode) {
        $error = '';
        $status = 500;
        $error_ = '';
        $options = get_option($this->oauth2AuthorizedOptions);
        try {
            $tokens = $this->adapter->generateAccessToken($options['code_verifier'], $authorizedCode);
            // Return the tokens to store in Db.
            return $tokens;
        } catch (AWeberOAuth2Exception $exc) {
            // AWeberOAuth2Exception: This exception occurs when the authorization code entered by used is wrong.
            $error = $exc->getMessage();
            $status = $exc->status;
            $error_ = get_class($exc);
        } catch (AWeberAPIException $exc) {
            // AWeberAPIException: This exception occurs when not able to connect to AWeber API.
            // Either API is down or Network not avaliable or response status is >= 400.
            $error = $exc->getMessage();
            $status = $exc->status;
        } catch (AWeberException $exc) {
            // Get the error message.
            $error = $exc->getMessage();
            // Get the error status, if it has
            $status = isset($exc->status) ? $exc->status : '';
            $error_ = get_class($exc);
        } catch (\Exception $exc) {
            $error = $exc->getMessage();
            $status = 500;
            $error_ = get_class($exc);
        } catch (\Throwable $exc) {
            $error = $exc->getMessage();
            $status = 500;
            $error_ = get_class($exc);
        }
        return array('error' => $error, 'status' => $status, 'error_c' => $error_);
    }

    /**
     * Retrieve the account associated with the token.
     *
     * @return AWeberEntry the entry object associated with the account
     * @raises AWeberException when something goes wrong
     */
    public function getAccount() {
        // Request to get an account information.
        $responseBody = $this->adapter->request('GET', '/accounts');
        $accounts = $this->readResponse($responseBody, '/accounts');
        return $accounts[0];
    }

    public function revokeAccessToken() {
        try {
            $this->adapter->revokeAccessToken();
        } catch (AWeberException $exc) {
            // If exception occured. silently fail
            // and delete the tokens from database.
        } catch (\Exception $exc) {
            // Catch and be silent.
        } catch (\Throwable $exc) {
            // Catch and be silent.
        }
    }
}
