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
    public static string $requestErrorDebug;

    /**
     * @deprecated since 3.6.0, use toArrayGet() instead.
     */
    public static function httpGet(string $url, array $options = [], bool $toArray = true)
    {
        return self::requestInternal('GET', $url, $options, $toArray, true);
    }

    /**
     * @deprecated since 3.6.0, use toArrayPost() instead.
     */
    public static function httpPost(string $url, array $options = [], bool $toArray = true)
    {
        return self::requestInternal('POST', $url, $options, $toArray, true);
    }

    /**
     * @deprecated since 3.6.0
     */
    public static function httpPut(string $url, array $options = [], bool $toArray = true)
    {
        return self::requestInternal('PUT', $url, $options, $toArray, true);
    }

    /**
     * @deprecated since 3.6.0
     */
    public static function httpPatch(string $url, array $options = [], bool $toArray = true)
    {
        return self::requestInternal('PATCH', $url, $options, $toArray, true);
    }

    /**
     * @deprecated since 3.6.0, use toArrayLaxGet() instead.
     */
    public static function httpLaxGet(string $url, array $options = [], bool $toArray = true)
    {
        return self::requestInternal('GET', $url, $options, $toArray, false);
    }

    /**
     * @deprecated since 3.6.0, use toArrayLaxPost() instead.
     */
    public static function httpLaxPost(string $url, array $options = [], bool $toArray = true)
    {
        return self::requestInternal('POST', $url, $options, $toArray, false);
    }

    /**
     * @deprecated since 3.6.0
     */
    public static function httpLaxPut(string $url, array $options = [], bool $toArray = true)
    {
        return self::requestInternal('PUT', $url, $options, $toArray, false);
    }

    /**
     * @deprecated since 3.6.0
     */
    public static function httpLaxPatch(string $url, array $options = [], bool $toArray = true)
    {
        return self::requestInternal('PATCH', $url, $options, $toArray, false);
    }


    public static function toArrayGet(string $url, array $options = []): ?array
    {
        return self::requestInternal('GET', $url, $options, true, true);
    }

    public static function toArrayPost(string $url, array $options = []): ?array
    {
        return self::requestInternal('POST', $url, $options, true, true);
    }

    public static function toArrayLaxGet(string $url, array $options = []): ?array
    {
        return self::requestInternal('GET', $url, $options, true, false);
    }

    public static function toArrayLaxPost(string $url, array $options = []): ?array
    {
        return self::requestInternal('POST', $url, $options, true, false);
    }

    private static function requestInternal(string $method, string $url, array $options, bool $toArray, bool $throw)
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
            self::$requestErrorDebug = $resp->getInfo('debug');
            return null;
        }

        if ($throw) {
            if ($statusCode < 200 || $statusCode >= 300) {
                self::$requestError = 'Status Code Error';
                self::$requestErrorDebug = $resp->getInfo('debug');
                return null;
            }
        }

        try {
            return $toArray ? $resp->toArray($throw) : $resp->getContent($throw);
        } catch (ClientExceptionInterface $e) {
            self::$requestError = 'Invalid Request';
            self::$requestErrorDebug = $resp->getInfo('debug');
            return null;
        } catch (DecodingExceptionInterface $e) {
            self::$requestError = 'Invalid Response';
            self::$requestErrorDebug = $resp->getInfo('debug');
            return null;
        } catch (RedirectionExceptionInterface $e) {
            self::$requestError = 'Too Many Redirects';
            self::$requestErrorDebug = $resp->getInfo('debug');
            return null;
        } catch (ServerExceptionInterface $e) {
            self::$requestError = 'Remote Service Error';
            self::$requestErrorDebug = $resp->getInfo('debug');
            return null;
        } catch (TransportExceptionInterface $e) {
            self::$requestError = 'Network Error';
            self::$requestErrorDebug = $resp->getInfo('debug');
            return null;
        }
    }
}
