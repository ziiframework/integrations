<?php

declare(strict_types=1);

namespace Zii\Integrations;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class SymfonyHttp
{
    private array $initOptions = [];

    public function __construct(string $base_uri = null)
    {
        if ($base_uri !== null) {
            $this->initOptions['base_uri'] = $base_uri;
        }
    }

    public function toArrayGET(string $url, array $query = [], array $options = [], bool $strict = true): ?array
    {
        if (count($query) !== 0) {
            $options['query'] = $query;
        }

        return $this->requestInternal('GET', $url, $options, true, $strict);
    }

    public function toArrayPOST(string $url, array $json = [], array $options = [], bool $strict = true): ?array
    {
        if (count($json) !== 0) {
            $options['json'] = $json;
        }

        return $this->requestInternal('POST', $url, $options, true, $strict);
    }

    public function toStringGET(string $url, array $query = [], array $options = [], bool $strict = true): ?string
    {
        if (count($query) !== 0) {
            $options['query'] = $query;
        }

        return $this->requestInternal('GET', $url, $options, false, $strict);
    }

    public function toStringPOST(string $url, array $json = [], array $options = [], bool $strict = true): ?string
    {
        if (count($json) !== 0) {
            $options['json'] = $json;
        }

        return $this->requestInternal('POST', $url, $options, false, $strict);
    }

    /**
     * @return array|string|null
     */
    private function requestInternal(string $method, string $url, array $options, bool $toArray, bool $strict)
    {
        /** @var \Symfony\Component\HttpClient\CurlHttpClient $client */
        $client = HttpClient::create();

        try {
            /** @var \Symfony\Component\HttpClient\Response\CurlResponse $http_resp */
            $http_resp = $client->request($method, $url, array_merge($this->initOptions, $options));
        } catch (TransportExceptionInterface $e) {
            $this->_error = $e->getMessage();
            return null;
        }

        try {
            $statusCode = $http_resp->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            $this->_error = $e->getMessage();
            $this->_debug = $http_resp->getInfo('debug');
            return null;
        }

        if ($statusCode !== 200) {
            $this->_error = "Invalid HTTP status code: $statusCode";
            $this->_debug = $http_resp->getInfo('debug');
            return null;
        }

        if ($toArray) {
            try {
                return $http_resp->toArray($strict);
            } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface|DecodingExceptionInterface $e) {
                $this->_error = $e->getMessage();
                $this->_debug = $http_resp->getInfo('debug');
                return null;
            }
        } else {
            try {
                return $http_resp->getContent($strict);
            } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
                $this->_error = $e->getMessage();
                $this->_debug = $http_resp->getInfo('debug');
                return null;
            }
        }
    }


    private ?string $_error = null;

    public function hasError(): bool
    {
        return $this->_error !== null;
    }

    public function getError(): ?string
    {
        return $this->_error;
    }


    private ?string $_debug = null;

    public function getDebug(): ?string
    {
        return $this->_debug;
    }
}
