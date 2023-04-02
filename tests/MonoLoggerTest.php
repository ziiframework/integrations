<?php

namespace yiiunit\integrations;

use Symfony\Component\Filesystem\Filesystem;
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
        $dir = RUNTIME_DIR . '/' . time() . '_' . random_int(111_111, 999_999);

        $logger = new MonoLogger($dir, 'UnitTest', 'test-session-id');

        $uname = pf_posix_username('nobody');
        $date = date('Ym');

        foreach (['debug', 'info', 'warning', 'error'] as $idx => $level) {
            $logger->$level("test $level message");
            $this->assertSame(2 + $idx + 1, count(scandir($dir)));

            $log_file = $dir . "/$level.$date.$uname.unit-test.log";

            $log_file_contents = file_get_contents($log_file);

            // default without GlobalVars
            $this->assertStringContainsString(
                sprintf('UnitTest.%s: [test %s message] {', strtoupper($level), $level),
                $log_file_contents
            );
            $this->assertStringContainsString('"sessionId":"test-session-id"', $log_file_contents);
            $this->assertStringContainsString('"context":null', $log_file_contents);
            $this->assertStringNotContainsString('"__SERVER":{', $log_file_contents);

            // set with GlobalVars
            $logger->withGlobalVars(true);
            $logger->$level("test $level message");
            $log_file_contents = file_get_contents($log_file);
            $this->assertStringContainsString('"__SERVER":{', $log_file_contents);
            dump($log_file_contents);
        }

        // avoid "resource busy"
        $logger->close();

        $fs = new Filesystem();
        $fs->remove($dir);
    }
}
