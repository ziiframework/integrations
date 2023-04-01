<?php

declare(strict_types=1);

namespace Zii\Integrations;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\Response\CurlResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class SymfonyHttp
{
    public function toArrayGET(string $url, array $options = [], bool $strict = true): ?array
    {
        return $this->requestInternal('GET', $url, $options, true, $strict);
    }

    public function toArrayPOST(string $url, array $options = [], bool $strict = true): ?array
    {
        return $this->requestInternal('POST', $url, $options, true, $strict);
    }

    public function toStringGET(string $url, array $options = [], bool $strict = true): ?string
    {
        return $this->requestInternal('GET', $url, $options, false, $strict);
    }

    public function toStringPOST(string $url, array $options = [], bool $strict = true): ?string
    {
        return $this->requestInternal('POST', $url, $options, false, $strict);
    }

    /**
     * @return array|string|null
     */
    private function requestInternal(string $method, string $url, array $options, bool $toArray, bool $strict)
    {
        $client = HttpClient::create();

        try {
            /** @var CurlResponse $http_resp */
            $http_resp = $client->request($method, $url, $options);
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
