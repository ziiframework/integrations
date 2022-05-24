<?php

declare(strict_types=1);

namespace Zii\Integrations;

use Webmozart\Assert\Assert;

final class WeixinMiniprogram extends ClassObject
{
    public string $appId;
    public string $appSecret;

    public string $entrypoint = 'https://api.weixin.qq.com';

    private array $_jscode2session = [];

    public function jscode2session(string $jscode): array
    {
        if (isset($this->_jscode2session[$jscode])) {
            return $this->_jscode2session[$jscode];
        }

        $this->_jscode2session[$jscode] = SymfonyHttpClient::httpGet("{$this->entrypoint}/sns/jscode2session", [
            'query' => [
                'appid' => $this->appId,
                'secret' => $this->appSecret,
                'js_code' => $jscode,
                'grant_type' => 'authorization_code',
            ],
        ]);

        Assert::isArray($this->_jscode2session[$jscode]);

        return $this->_jscode2session[$jscode];
    }

    public function jscode2openid(string $jscode): ?string
    {
        $result = $this->jscode2session($jscode);

        if (isset($result['openid']) && pf_is_string_filled($result['openid'])) {
            return $result['openid'];
        }

        return null;
    }

    public function jscode2unionid(string $jscode): ?string
    {
        $result = $this->jscode2session($jscode);

        if (isset($result['unionid']) && pf_is_string_filled($result['unionid'])) {
            return $result['unionid'];
        }

        return null;
    }

    public function jscode2sessionKey(string $jscode): ?string
    {
        $result = $this->jscode2session($jscode);

        if (isset($result['session_key']) && pf_is_string_filled($result['session_key'])) {
            return $result['session_key'];
        }

        return null;
    }

    private array $_fetchAccessToken = [];

    public function fetchAccessToken(): ?string
    {
        if (isset($this->_fetchAccessToken[$this->appId])) {
            return $this->_fetchAccessToken[$this->appId];
        }

        $result = SymfonyHttpClient::httpGet("{$this->entrypoint}/cgi-bin/token", [
            'query' => [
                'appid' => $this->appId,
                'secret' => $this->appSecret,
                'grant_type' => 'client_credential',
            ],
        ]);

        Assert::stringNotEmpty($result['access_token'] ?? null);

        $this->_fetchAccessToken[$this->appId] = $result['access_token'];

        return $this->_fetchAccessToken[$this->appId];
    }
}
