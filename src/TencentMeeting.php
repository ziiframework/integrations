<?php

declare(strict_types=1);

namespace Zii\Integrations;

use Webmozart\Assert\Assert;

final class TencentMeeting
{
    public string $appId;
    public string $sdkId;
    public string $accessKeyId;
    public string $accessKeySecret;

    public function makeHeaders(array $extra = []): array
    {
        return array_merge([
            'Content-Type' => 'application/json',
            'X-TC-Key' => $this->accessKeyId,
            'X-TC-Timestamp' => strval(time()),
            'X-TC-Nonce' => strval(pf_mt_rand(100000, 999999)),
            'AppId' => $this->appId,
            'SdkId' => $this->sdkId,
            'X-TC-Registered' => '1',
        ], $extra);
    }

    public function makeSignWithGet(string $url, array $headers): string
    {
        return $this->makeSignInternal('GET', $url, $headers, '');
    }

    public function makeSignWithPost(string $url, array $headers, string $body): string
    {
        return $this->makeSignInternal('POST', $url, $headers, $body);
    }

    public function makeSignWithPut(string $url, array $headers, string $body): string
    {
        return $this->makeSignInternal('PUT', $url, $headers, $body);
    }

    public function makeSignWithPatch(string $url, array $headers, string $body): string
    {
        return $this->makeSignInternal('PATCH', $url, $headers, $body);
    }

    private function makeSignInternal(string $method, string $url, array $headers, string $body): string
    {
        $url_path_with_query = parse_url($url, PHP_URL_PATH);
        $url_query = parse_url($url, PHP_URL_QUERY);

        Assert::true(pf_is_string_filled($url_path_with_query));

        if (pf_is_string_filled($url_query)) {
            $url_path_with_query .= '?' . $url_query;
        }

        $sh = implode('&', [
            'X-TC-Key=' . $headers['X-TC-Key'],
            'X-TC-Nonce=' . $headers['X-TC-Nonce'],
            'X-TC-Timestamp=' . $headers['X-TC-Timestamp'],
        ]);

        $request = "{$method}\n{$sh}\n{$url_path_with_query}\n{$body}";

        return base64_encode(hash_hmac('sha256', $request, $this->accessKeySecret));
    }
}
