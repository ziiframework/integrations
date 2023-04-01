<?php

namespace yiiunit\integrations;

use Symfony\Contracts\HttpClient\ResponseInterface;
use Zii\Integrations\SymfonyHttp;

class SymfonyHttpTest extends TestCase
{
    public function testRequests()
    {
        $sh = new SymfonyHttp('https://httpbin.org/');

        // toArrayGET
        $http_resp = $sh->toArrayGET('/anything');
        $this->assertFalse($sh->hasError());
        $this->assertInstanceOf(ResponseInterface::class, $sh->getResponse());
        $this->assertSame('https://httpbin.org/anything', $http_resp['url']);
        $this->assertSame('GET', $http_resp['method']);

        // toArrayGET with query
        $http_resp = $sh->toArrayGET('/anything', ['q' => 'foo', 'p' => 'bar']);
        $this->assertSame('https://httpbin.org/anything?q=foo&p=bar', $http_resp['url']);


        // toArrayPOST
        $http_resp = $sh->toArrayPOST('/anything');
        $this->assertFalse($sh->hasError());
        $this->assertInstanceOf(ResponseInterface::class, $sh->getResponse());
        $this->assertSame('POST', $http_resp['method']);

        // toArrayPOST with json
        $http_resp = $sh->toArrayPOST('/anything?q=foo&p=bar', ['a' => 'aaa', 'b' => 'bbb']);
        $this->assertSame('https://httpbin.org/anything?q=foo&p=bar', $http_resp['url']);
        $this->assertSame('POST', $http_resp['method']);
        $this->assertSame('{"a":"aaa","b":"bbb"}', $http_resp['data']);
        $this->assertSame('application/json', $http_resp['headers']['Content-Type']);

        $http_resp = $sh->toArrayPUT('/anything');
        $this->assertSame('PUT', $http_resp['method']);

        $http_resp = $sh->toArrayPATCH('/anything');
        $this->assertSame('PATCH', $http_resp['method']);

        $http_resp = $sh->toArrayDELETE('/anything');
        $this->assertSame('DELETE', $http_resp['method']);


        // toStringGET
        $http_resp = $sh->toStringGET('/anything');
        $this->assertStringContainsString('"method": "GET"', $http_resp);

        // toStringPOST
        $http_resp = $sh->toStringPOST('/anything');
        $this->assertStringContainsString('"method": "POST"', $http_resp);

        $http_resp = $sh->toStringPUT('/anything');
        $this->assertStringContainsString('"method": "PUT"', $http_resp);

        $http_resp = $sh->toStringPATCH('/anything');
        $this->assertStringContainsString('"method": "PATCH"', $http_resp);

        $http_resp = $sh->toStringDELETE('/anything');
        $this->assertStringContainsString('"method": "DELETE"', $http_resp);
    }

    public function testErrors()
    {
        $random_host = 'www.test' . bin2hex(random_bytes(5)) . '.com';

        $sh = new SymfonyHttp("https://$random_host/");

        $http_resp = $sh->toArrayGET('/');

        $this->assertNull($http_resp);
        $this->assertTrue($sh->hasError());
        $this->assertNull($sh->getResponse());
        $this->assertStringContainsString("Could not resolve host: $random_host", $sh->getError());
        $this->assertStringContainsString("* Could not resolve host: $random_host", $sh->getDebug());
        $this->assertStringContainsString("* Closing connection 0", $sh->getDebug());

        $http_resp = $sh->toArrayGET('https://www.microsoft.com/ajsdkjasndknaskdnaslkdlas');
        $this->assertNull($http_resp);
        $this->assertTrue($sh->hasError());
        $this->assertInstanceOf(ResponseInterface::class, $sh->getResponse());
        $this->assertSame("Invalid HTTP status code: 404", $sh->getError());
        $this->assertStringContainsString("* Connected to www.microsoft.com", $sh->getDebug());
    }
}
