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

            $get_log_contents = fn() => file_get_contents($dir . "/$level.$date.$uname.unit-test.log");

            $this->assertStringContainsString(
                sprintf(
                    'UnitTest.%s: [test %s message] {"sessionId":"test-session-id","context":null}',
                    strtoupper($level),
                    $level
                ),
                $get_log_contents()
            );

            // default without GlobalVars
            $this->assertStringNotContainsString('"__SERVER":{', $get_log_contents());
            dump(array_keys($GLOBALS));

            // set with GlobalVars
            $logger->withGlobalVars(true);
            $logger->$level("test $level message");
            $this->assertStringContainsString('"__SERVER":{', $get_log_contents());
        }

        // avoid "resource busy"
        $logger->close();

        $fs = new Filesystem();
        $fs->remove($dir);
    }
}
