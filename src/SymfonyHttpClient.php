<?php

declare(strict_types=1);

namespace Zii\Integrations;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

final class SymfonyHttpClient
{
    public static string $requestError;

    public static bool $allowStatusCodeError = false;

    public static function httpGet(string $url, array $options = [], bool $toArray = true)
    {
        return self::requestInternal('GET', $url, $options, $toArray);
    }

    public static function httpPost(string $url, array $options = [], bool $toArray = true)
    {
        return self::requestInternal('POST', $url, $options, $toArray);
    }

    private static function requestInternal(string $method, string $url, array $options = [], bool $toArray = true)
    {
        $client = HttpClient::create();

        try {
            $resp = $client->request($method, $url, $options);
        } catch (TransportExceptionInterface $e) {
            self::$requestError = 'Network Error';
            return null;
        }

        try {
            $statusCode = $resp->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            self::$requestError = 'Network Error';
            return null;
        }

        if (!self::$allowStatusCodeError) {
            if ($statusCode < 200 || $statusCode >= 300) {
                self::$requestError = 'Status Code Error';
                return null;
            }
        }

        try {
            return $toArray ? $resp->toArray() : $resp->getContent();
        } catch (ClientExceptionInterface $e) {
            self::$requestError = 'Invalid Request';
            return null;
        } catch (DecodingExceptionInterface $e) {
            self::$requestError = 'Invalid Response';
            return null;
        } catch (RedirectionExceptionInterface $e) {
            self::$requestError = 'Too Many Redirects';
            return null;
        } catch (ServerExceptionInterface $e) {
            self::$requestError = 'Remote Service Error';
            return null;
        } catch (TransportExceptionInterface $e) {
            self::$requestError = 'Network Error';
            return null;
        }
    }
}
