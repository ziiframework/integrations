<?php

declare(strict_types=1);

namespace Zii\Integrations;

final class TencentMeeting
{
    public string $accessKeyId;
    public string $accessKeySecret;

    public function sign(array $headers, string $method, ?string $body = null): string
    {
        $method = strtoupper($method);

        if ($body === null) {
            $body = "";
        }

        $headers_stringify = "X-TC-Key={$this->accessKeyId}&X-TC-Nonce={$headers['X-TC-Nonce']}&X-TC-Timestamp={$headers['X-TC-Timestamp']}";

        $request = "{$method}\n{$headers_stringify}\n{$headers['URI']}\n{$body}";

        return base64_encode(hash_hmac('sha256', $request, $this->accessKeySecret));
    }
}
