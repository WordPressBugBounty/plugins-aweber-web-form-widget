<?php
namespace AWeberWebFormPluginNamespace;

# WordpressHttpResponse
#
# Date    September 2019
#
# A basic Wordpress Http response for PHP
#
# See the README for documentation/examples or https://developer.wordpress.org/plugins/http-api/
#

class WordpressHttpResponse {
    public $body = '';
    public $headers = array();

    public function __construct($response) {
    	$http_response = $response['http_response']->get_response_object();

        $this->headers = array(
            'Http-Version' => $http_response->protocol_version,
            'Location'  => wp_remote_retrieve_header($response, 'Location'),
            'Status-Code' => $http_response->status_code . ' ' . explode(' ', $http_response->raw)['2'],
            'Status' => $http_response->status_code,
            'Content-Type' => wp_remote_retrieve_header($response, 'Content-Type'),
            'Date' => wp_remote_retrieve_header($response, 'Date'),
            'Etag' => wp_remote_retrieve_header($response, 'Etag'),
            'Content-Length' => wp_remote_retrieve_header($response, 'Content-Length'),
            'Set-Cookie' => wp_remote_retrieve_header($response, 'Set-Cookie')
        );
        $this->body = wp_remote_retrieve_body($response);
    }

    public function __toString() {
        return $this->body;
    }

    public function headers() {
        return $this->headers;
    }
}