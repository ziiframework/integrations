<?php

namespace yiiunit\integrations;

use Symfony\Component\Finder\Finder;
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
        $finder = new Finder();

        foreach (['debug', 'info', 'warning', 'error'] as $idx => $level) {
            $logger->$level("test $level message");
            $this->assertSame(3 + $idx + 1, count(scandir(RUNTIME_DIR)));

            $files = $finder->files()->in(RUNTIME_DIR)->name("/$level\.\d{6}\.unit_test\.log$/");

            $this->assertSame(1, $files->count());

            foreach ($files as $file) {
                $this->assertStringContainsString(
                    sprintf(
                        'UnitTest.%s: [test %s message] {"sessionId":"test-session-id","context":null}',
                        strtoupper($level),
                        $level
                    ),
                    $file->getContents()
                );
            }
        }
    }
}
