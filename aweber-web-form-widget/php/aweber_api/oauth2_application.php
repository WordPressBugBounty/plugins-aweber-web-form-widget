<?php
namespace AWeberWebFormPluginNamespace;


/**
 * AWeberOAuth2ServiceProvider
 *
 * Provides specific AWeber information or implementing OAuth.
 */
class AWeberOAuth2ServiceProvider {

    /**
     * @var String Location for API calls
     */
    public $baseUri = 'https://api.aweber.com/1.0';

    public function getBaseUri() {
        return $this->baseUri;
    }

    public function removeBaseUri($url) {
        return str_replace($this->getBaseUri(), '', $url);
    }
}


#[\AllowDynamicProperties]
class OAuth2Application {

    // Option Name to store the OAuth2 tokens in Database.
    private $oauth2TokensOptions = 'AWeberOauth2TokensOptions';

    private $clientId = 'eipSRwSn5aZJAZ7aTwOCnsS6IC3JKV7M';

    private $scopes = array(
        'account.read',
        'list.read',
        'subscriber.read',
        'subscriber.write',
        'landing-page.read'
    );

    private $responseType = 'code';

    private $codeChallengeMethod = 'S256';

    private $accessToken = null;

    private $refreshToken = null;

    private $expiresOn = null;

    protected $authorizeBaseURL = 'https://auth.aweber.com/oauth2/authorize';

    protected $tokenBaseURL = 'https://auth.aweber.com/oauth2/token';

    protected $revokeTokenURL = 'https://auth.aweber.com/oauth2/revoke';

    public function __construct(
            $accessToken=null,
            $refreshToken=null,
            $expiresOn=null
    ) {
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
        $this->expiresOn = $expiresOn;

        $this->app = new AWeberOAuth2ServiceProvider();
    }

    private function getCodeChallenge($codeVerifier) {
        $challengeBytes = hash('sha256', $codeVerifier, true);
        return rtrim(strtr(base64_encode($challengeBytes), "+/", "-_"), "=");
    }

    private function getState() {
        // State token, a uuid is fine here
        return uniqid();
    }

    private function getClientId() {
        return $this->clientId;
    }

    private function getAuthorizeQuery($codeVerifier) {
        $authQueryParams = array(
            'response_type' => $this->responseType,
            'client_id'     => $this->clientId,
            'state'         => $this->getState(),
            'redirect_uri'  => 'urn:ietf:wg:oauth:2.0:oob',
            'code_challenge'    => $this->getCodeChallenge($codeVerifier),
            'code_challenge_method' => $this->codeChallengeMethod
        );
        return http_build_query($authQueryParams).
                '&scope=' . implode('+', $this->scopes);
    }

    private function getAccessTokenRequest($codeVerifier, $authorizedCode) {
        return array(
            'grant_type' => 'authorization_code',
            'code'      => $authorizedCode,
            'client_id' => $this->clientId,
            'code_verifier' => $codeVerifier,
        );
    }

    private function getRefreshTokenRequest() {
        return array(
            'grant_type'    => 'refresh_token',
            'client_id'     => $this->clientId,
            'refresh_token' => $this->refreshToken
        );
    }

    private function getRevokeTokenRequest() {
        return array(
            'client_id' => $this->clientId,
            'token'     => $this->refreshToken,
            'token_type_hint'   => 'refresh_token'
        );
    }

    private function get_api_response($url, $response) {
        if (!is_wp_error($response)) {
            return new WordpressHttpResponse($response);
        }
        // If the request failed, show the error message.
        $msg = '(' . $response->get_error_code() . ' - ' . $response->get_error_message() . ')';
        throw new AWeberAPIException(array(
            'error_description' => 'Unable to connect to the AWeber API. ' . esc_html($msg),
            'type' => 'APIUnreachableError',
            'documentation_url' => 'https://labs.aweber.com/docs/troubleshooting'
        ), esc_url($url));
    }

    private function http_post($url, $requestBody) {
        $options = $this->getDefaultOptions();
        $options['body'] = $requestBody;
        return $this->get_api_response($url, wp_remote_post($url, $options));
    }

    private function http_get($url) {
        $options = $this->getDefaultOptions();
        return $this->get_api_response($url, wp_remote_get($url, $options));
    }

    private function serializeRequestBody($requestBody) {
        foreach ($requestBody as $key => $value) {
            if (is_array($value)) {
                $requestBody[$key] = json_encode($value);
            }
        }
        return $requestBody;
    }

    private function makeRequest($method, $url, $requestBody=array(), $options=array()) {
        switch (strtoupper($method)) {
            case 'POST':
                $response  = $this->http_post($url, $requestBody);
                break;
            case 'GET':
                $response  = $this->http_get($url);
                break;
            default:
                throw new AWeberMethodNotImplemented();
        }

        $responseBody = json_decode($response->body, true);
        // This happens, when the access token sent in the
        // Authorization header is invalid or expired.
        if ($response->headers['Status'] == 401
            && $responseBody['error'] == 'invalid_token'
        ) {
            throw new AWeberOAuth2TokenExpired('invalid token', 401);
        }

        // This happens, when the wrong or invalid authorization code is sent.
        if ($response->headers['Status'] == 400
            and $responseBody['error'] == 'invalid_request'
        ) {
            throw new AWeberOAuth2Exception('invalid request', 400);
        }

        // This is used for all other AWeber API.
        if($response->headers['Status'] >= 400) {
            array_merge($responseBody, array('status', $response->headers['Status']));
            $error = $responseBody['error'];
            throw new AWeberAPIException(
                array(
                    'type' => esc_html($error['type']),
                    'status' => esc_html($error['status']),
                    'error_description' => esc_html($error['error_description']),
                    'documentation_url' => esc_url($error['documentation_url']),
                ), esc_url($url));
        }
        // If the Options are set, then return with respect to the options,
        // dont return whole response body.
        if (!empty($options['return'])) {
            if ($options['return'] == 'status') {
                return $response->headers['Status-Code'];
            }
            if ($options['return'] == 'headers') {
                return $response->headers;
            }
            if ($options['return'] == 'integer') {
                return intval($response->body);
            }
        }
        return $responseBody;
    }

    private function getDefaultOptions() {
        $headers = array('Accept'  => 'application/json');
        if (!empty($this->accessToken)) {
            $headers['Authorization'] = 'Bearer ' . $this->accessToken;
        }
        return array(
            'headers' => $headers,
            'user-agent' => 'AWeber-Wordpress/1.0 ' . ' WordPress/' . get_bloginfo('version'),
        );
    }

    private function isTokenExpired() {
        // If the currnet timestamp is greater than the token timestamp.
        // then the token is expired. Time to refresh the token.
        return $this->expiresOn < time();
    }

    public function getAuthorizeUrl($codeVerifier) {
        $authorizeQuery = $this->getAuthorizeQuery($codeVerifier);
        return $this->authorizeBaseURL . '?' . $authorizeQuery;
    }

    public function generateAccessToken($codeVerifier, $authorizedCode) {
        $tokenQuery = $this->getAccessTokenRequest($codeVerifier, $authorizedCode);
        // Make a post request.
        $tokens  = $this->makeRequest('POST', $this->tokenBaseURL, $tokenQuery);
        // Set the Token values
        $this->accessToken = $tokens['access_token'];
        $this->refreshToken = $tokens['refresh_token'];
        $this->expiresOn = time() + $tokens['expires_in'];
        // Also, append the client-id and timestamp when it expires.
        return array_merge($tokens, array(
            'client_id'  => $this->getClientId(),
            'expires_on' => $this->expiresOn
        ));
    }

    public function refreshAccessToken() {
        $tokenQuery = $this->getRefreshTokenRequest();
        // Make a post request.
        $tokens  = $this->makeRequest('POST', $this->tokenBaseURL, $tokenQuery);
        // Set the Token values
        $this->accessToken = $tokens['access_token'];
        $this->refreshToken = $tokens['refresh_token'];
        $this->expiresOn = time() + $tokens['expires_in'];

        $tokens['expires_on'] = $this->expiresOn;
        $tokens['client_id'] = $this->getClientId();

        // Stored the refreshed tokens in the Database.
        update_option($this->oauth2TokensOptions, $tokens);
    }

    public function revokeAccessToken() {
        $tokenQuery = $this->getRevokeTokenRequest();
        // delete the access token.
        $this->accessToken = null;
        $this->makeRequest('POST', $this->revokeTokenURL, $tokenQuery);

        // If the response status is 200. The token is deleted.
        $this->accessToken = null;
        $this->refreshToken = null;
        $this->expiresOn = null;
    }

    public function request($method, $uri, $requestBody=array(), $options=array()) {
        // Check if the access token got expired or not.
        if ($this->isTokenExpired()) {
            // Delete the access token, so it wont be included in the header.
            $this->accessToken = null;
            // If the token is expired, then refresh it.
            $this->refreshAccessToken();
        }
        // Remove the baseURL if it already have.
        $uri = $this->app->removeBaseUri($uri);
        $url = $this->app->getBaseUri() . $uri;

        // WARNING: non-primative items in data must be json
        // serialized in GET and POST.
        $requestBody = $this->serializeRequestBody($requestBody);
        for ($attempt=0; $attempt<2; $attempt++) {
            try {
                $responseBody = $this->makeRequest($method, $url, $requestBody, $options);
                break;
            } catch (AWeberOAuth2TokenExpired $exc) {
                // if the request fails due to access_token expired or invalid
                // access token sent. Set the token to null, so that it wont be
                // included in the Authorization header.
                $this->accessToken = null;
                if ($attempt == 0) {
                    // Catch this exception and refresh token.
                    $this->refreshAccessToken();
                } else {
                    // Remove the tokens from the DB and try to connect to
                    // AWeber Again.
                    throw new AWeberTokenRefreshException();
                }
            }
        }
        if (empty($options['allow_empty']) && !isset($responseBody)) {
            throw new AWeberResponseError(esc_url($uri));
        }
        return $responseBody;
    }

    public function parseAsError() {
        // This function will be called from the AWeberAPIBase.
        // Nothing to do here. It checks for the OAuth1 exception.
        // but for the OAuth2 we are handling this in the request module only.
    }
}
