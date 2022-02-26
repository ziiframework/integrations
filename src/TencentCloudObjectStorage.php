<?php

declare(strict_types=1);

namespace Zii\Integrations;

final class TencentCloudObjectStorage
{
    public string $accessKeyId;
    public string $accessKeySecret;

    public int $effectiveSeconds = 180;

    private int $_timestamp;

    public function __construct()
    {
        $this->_timestamp = time();
    }

    // step 1
    private function prepareKeyTime(): string
    {
        return $this->_timestamp . ';' . ($this->_timestamp + $this->effectiveSeconds);
    }

    // step 2
    private function preparePolicy(): string
    {
        $policy = [
            'expiration' => gmdate('Y-m-d\TH:i:s.v\Z', ($this->_timestamp + $this->effectiveSeconds)),
            'conditions' => [
                ['q-sign-algorithm' => 'sha1'],
                ['q-ak' => $this->accessKeyId],
                ['q-sign-time' => $this->prepareKeyTime()],
            ],
        ];

        return json_encode_320($policy);
    }

    // step 3
    private function prepareSignKey(): string
    {
        return hash_hmac('sha1', $this->prepareKeyTime(), $this->accessKeySecret);
    }

    // step 4
    private function preparePostRequestHash(): string
    {
        return sha1($this->preparePolicy());
    }

    // step 5
    private function preparePostRequestSignature(): string
    {
        return hash_hmac('sha1', $this->preparePostRequestHash(), $this->prepareSignKey());
    }

    // step 6
    public function generateAuthorization(): array
    {
        return [
            'policy' => base64_encode($this->preparePolicy()),
            'q-sign-algorithm' => 'sha1',
            'q-ak' => $this->accessKeyId,
            'q-key-time' => $this->prepareKeyTime(),
            'q-signature' => $this->preparePostRequestSignature(),
            'success_action_status' => 200,
        ];
    }
}
