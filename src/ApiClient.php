<?php

namespace Pebble\Http;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use Pebble\Security\JWT;

class ApiClient
{
    protected $base_url = '';
    protected $api_key = '';
    protected $api_alg = 'HS256';

    protected $client;
    protected $payload = [];
    protected $iss = 'sopheos';
    protected $user_agent;
    protected $hash;

    // -------------------------------------------------------------------------

    public function __construct(string $user_agent = 'sopheos')
    {
        $this->client = new Client();
        $this->user_agent = $user_agent;
        $this->hash = sha1($user_agent);
    }

    /**
     * @return \static
     */
    public static function create(string $user_agent = 'sopheos')
    {
        return new static($user_agent);
    }

    // -------------------------------------------------------------------------

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->base_url;
    }

    /**
     * @return string
     */
    public function getKey(): string
    {
        return $this->api_key;
    }

    /**
     * @return string
     */
    public function getAlgorithm(): string
    {
        return $this->api_alg;
    }

    // -------------------------------------------------------------------------

    /**
     * @param string $key
     * @param mixed $value
     * @return \static
     */
    public function addPayload(string $key, $value)
    {
        $this->payload[$key] = $value;
        return $this;
    }

    /**
     * @param string $key
     * @return \static
     */
    public function delPayload(string $key)
    {
        if (array_key_exists($key, $this->payload)) {
            unset($this->payload[$key]);
        }
        return $this;
    }

    // -------------------------------------------------------------------------

    /**
     * @param string $url
     * @param array $data
     * @return ApiResponse
     */
    public function get(string $url, array $data = []): ApiResponse
    {
        return $this->request('GET', $url, [
            RequestOptions::QUERY => $data
        ]);
    }

    /**
     * @param string $url
     * @param array $data
     * @return ApiResponse
     */
    public function post(string $url, array $data): ApiResponse
    {
        return $this->request('POST', $url, [
            RequestOptions::JSON => $data
        ]);
    }

    /**
     * @param string $url
     * @param array $data
     * @return ApiResponse
     */
    public function put(string $url, array $data): ApiResponse
    {
        return $this->request('PUT', $url, [
            RequestOptions::JSON => $data
        ]);
    }

    /**
     * @param string $url
     * @param array $data
     * @return ApiResponse
     */
    public function patch(string $url, array $data = []): ApiResponse
    {
        return $this->request('PATCH', $url, [
            RequestOptions::JSON => $data
        ]);
    }

    /**
     * @param string $url
     * @return ApiResponse
     */
    public function delete(string $url, array $data = []): ApiResponse
    {
        return $this->request('DELETE', $url, [
            RequestOptions::QUERY => $data
        ]);
    }

    /**
     * @param string $url
     * @param array $data
     * @return ApiResponse
     */
    public function file(string $url, array $data): ApiResponse
    {
        return $this->request('POST', $url, [
            RequestOptions::MULTIPART => $data
        ]);
    }

    /**
     * @param string $url
     * @param array $data
     * @return ApiResponse
     */
    public function options(string $url, array $data): ApiResponse
    {
        return $this->request('OPTIONS', $url, [
            RequestOptions::JSON => $data
        ]);
    }

    // -------------------------------------------------------------------------

    /**
     * @param string $method
     * @param string $uri
     * @param array $options
     * @return ApiResponse
     */
    protected function request(string $method, string $uri, array $options = []): ApiResponse
    {
        try {

            // HTTP_ERRORS : false by default
            if (!isset($options[RequestOptions::HTTP_ERRORS])) {
                $options[RequestOptions::HTTP_ERRORS] = false;
            }

            // User-Agent
            if (!isset($options[RequestOptions::HEADERS]['User-Agent'])) {
                $options[RequestOptions::HEADERS]['User-Agent'] = $this->user_agent;
            }

            // Authorization Key
            if (!isset($options[RequestOptions::HEADERS]['Authorization']) && ($token = $this->token())) {
                $options[RequestOptions::HEADERS]['Authorization'] = 'Bearer ' . $token;
            }

            $url = $this->base_url . $uri;
            $res = $this->client->request($method, $url, $options);
            $status = $res->getStatusCode();
            $headers = $res->getHeaders();
            $body = (string) $res->getBody();

            return new ApiResponse($status, $headers, $body);
        } catch (\Exception $ex) {
            trigger_error($ex);
            return new ApiResponse(418, [], '');
        }
    }

    /**
     * @return string
     */
    public function token(int $expire = 300): string
    {
        if (!$this->api_key) {
            return '';
        }

        $now = time();

        $payload = array_merge([
            'hash' => $this->hash,
            'iss' => $this->iss,
            'iat' => $now,
            'exp' => $now + $expire,
        ], $this->payload);

        return JWT::encode($payload, $this->api_key, $this->api_alg);
    }
}
