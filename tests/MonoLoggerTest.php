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

        dump(file_get_contents(RUNTIME_DIR . '/debug.202303.runner.unittest.log'));
        dump(file_get_contents(RUNTIME_DIR . '/info.202303.runner.unittest.log'));
        dump(file_get_contents(RUNTIME_DIR . '/warning.202303.runner.unittest.log'));
        dump(file_get_contents(RUNTIME_DIR . '/error.202303.runner.unittest.log'));

        clearstatcache();
        $this->assertTrue(count(scandir(RUNTIME_DIR)) === 7);
    }
}
