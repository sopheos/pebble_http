<?php

namespace Pebble\Http;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class ApiRequest
{
    private array $clientOptions =  [];
    private string $method = 'GET';
    private string $url;
    private array $options = [];

    // -------------------------------------------------------------------------

    public function __construct(string $url)
    {
        $this->clientOptions = [
            RequestOptions::HTTP_ERRORS => false
        ];

        $this->url = $url;
    }

    public static function create(string $url): static
    {
        return new static($url);
    }

    // -------------------------------------------------------------------------

    public function setClientOption(string $name, mixed $value): static
    {
        $this->clientOptions[$name] = $value;
        return $this;
    }

    public function timeout(int $seconds): static
    {
        return $this->setClientOption(RequestOptions::TIMEOUT, $seconds);
    }

    // -------------------------------------------------------------------------

    public function setMethod(string $method): static
    {
        $this->method = $method;
        return $this;
    }

    public function get(): static
    {
        return $this->setMethod('GET');
    }

    public function post(): static
    {
        return $this->setMethod('POST');
    }

    public function put(): static
    {
        return $this->setMethod('PUT');
    }

    public function patch(): static
    {
        return $this->setMethod('PATCH');
    }

    public function delete(): static
    {
        return $this->setMethod('DELETE');
    }

    public function options(): static
    {
        return $this->setMethod('options');
    }

    // -------------------------------------------------------------------------

    public function addHeader(string $name, mixed $value): static
    {
        $this->options[RequestOptions::HEADERS][$name] = $value;
        return $this;
    }

    public function auth(string $token, string $type = 'Bearer'): static
    {
        return $this->addHeader("Authorization", trim("{$type} {$token}"));
    }

    public function userAgent(string $userAgent): static
    {
        return $this->addHeader('User-Agent', $userAgent);
    }

    // -------------------------------------------------------------------------

    public function addOption(string $name, mixed $value): static
    {
        $this->options[$name] = $value;
        return $this;
    }

    public function queryParams(array $data): static
    {
        return $this->addOption(RequestOptions::QUERY, $data);
    }

    public function jsonParams(array $data): static
    {
        return $this->addOption(RequestOptions::JSON, $data);
    }

    public function fileParams(array $data): static
    {
        return $this->addOption(RequestOptions::MULTIPART, $data);
    }

    public function formParams(array $data): static
    {
        return $this->addOption(RequestOptions::FORM_PARAMS, $data);
    }

    public function body(mixed $data): static
    {
        return $this->addOption(RequestOptions::BODY, $data);
    }

    // -------------------------------------------------------------------------

    public function run(): ApiResponse
    {
        $client = new Client($this->clientOptions);
        $res = $client->request($this->method, $this->url, $this->options);
        $status = $res->getStatusCode();
        $headers = $res->getHeaders();
        $body = (string) $res->getBody();

        return new ApiResponse($status, $headers, $body);
    }

    // -------------------------------------------------------------------------
}
