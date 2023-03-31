<?php

namespace yiiunit\integrations;

use Zii\Integrations\MonoLogger;

class MonoLoggerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->mockApplication();
    }

    public function testLogger()
    {
        $logger = new MonoLogger(RUNTIME_DIR, 'UnitTest', 'test-session-id');

        $logger->debug('test debug message');
        clearstatcache();
        dump('after debug:', scandir(RUNTIME_DIR));

        $logger->info('test info message');
        clearstatcache();
        dump('after info:', scandir(RUNTIME_DIR));

        $logger->warning('test warning message');
        clearstatcache();
        dump('after warning:', scandir(RUNTIME_DIR));

        $logger->error('test error message');
        clearstatcache();
        dump('after error:', scandir(RUNTIME_DIR));

        clearstatcache();
        $this->assertTrue(count(scandir(RUNTIME_DIR)) === 7);
    }
}
