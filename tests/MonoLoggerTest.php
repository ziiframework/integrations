<?php

namespace yiiunit\integrations;

use Zii\Integrations\MonoLogger;

class MonoLoggerTest extends TestCase
{
    public function testLogger()
    {
        $logger = new MonoLogger(RUNTIME_DIR, 'UnitTest', 'test-session-id');

        $logger->debug('test debug message');
        dump('after debug:', scandir(RUNTIME_DIR));

        $logger->info('test info message');
        dump('after info:', scandir(RUNTIME_DIR));

        $logger->warning('test warning message');
        dump('after warning:', scandir(RUNTIME_DIR));

        $logger->error('test error message');
        dump('after error:', scandir(RUNTIME_DIR));

        $this->assertFileExists(count(scandir(RUNTIME_DIR)) === 7);
    }
}
