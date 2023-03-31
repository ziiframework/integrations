<?php

declare(strict_types=1);

namespace Zii\Integrations;

use DateTimeZone;
use Doctrine\Inflector\Language;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Yii;
use Doctrine\Inflector\InflectorFactory;

final class MonoLogger
{
    private string $_dir;
    private string $_category;
    private string $_sessionId;

    public function __construct(string $dir, string $category, string $sessionId)
    {
        $this->_dir = $dir;
        $this->_category = $category;
        $this->_sessionId = $sessionId;
    }

    private ?Logger $_logger = null;

    private function logger(): Logger
    {
        if ($this->_logger !== null) {
            return $this->_logger;
        }

        $inflector = InflectorFactory::createForLanguage(Language::ENGLISH)->build();

        $uname = pf_posix_username('nobody');
        $filename = $inflector->urlize($this->_category);
        $date = date('Ym');

        $this->_logger = new Logger(
            $this->_category,
            [
                new StreamHandler($this->_dir . sprintf('/error.%s.%s.%s.log',   $date, $uname, $filename), Logger::ERROR, false),
                new StreamHandler($this->_dir . sprintf('/warning.%s.%s.%s.log', $date, $uname, $filename), Logger::WARNING, false),
                new StreamHandler($this->_dir . sprintf('/info.%s.%s.%s.log',    $date, $uname, $filename), Logger::INFO, false),
                new StreamHandler($this->_dir . sprintf('/debug.%s.%s.%s.log',   $date, $uname, $filename), Logger::DEBUG, false),
            ],
            [],
            new DateTimeZone(Yii::$app->timeZone)
        );

        return $this->_logger;
    }

    /**
     * @param string|int|float $message
     * @param mixed|null $context
     * @return void
     */
    public function debug($message, $context = null): void
    {
        $this->logger()->debug('[' . $message . ']', ['sessionId' => $this->_sessionId, 'context' => $context]);
    }

    /**
     * @param string|int|float $message
     * @param mixed|null $context
     * @return void
     */
    public function info($message, $context = null): void
    {
        $this->logger()->info('[' . $message . ']', ['sessionId' => $this->_sessionId, 'context' => $context]);
    }

    /**
     * @param string|int|float $message
     * @param mixed|null $context
     * @return void
     */
    public function warning($message, $context = null): void
    {
        $this->logger()->warning('[' . $message . ']', ['sessionId' => $this->_sessionId, 'context' => $context]);
    }

    /**
     * @param string|int|float $message
     * @param mixed|null $context
     * @return void
     */
    public function error($message, $context = null): void
    {
        $this->logger()->error('[' . $message . ']', ['sessionId' => $this->_sessionId, 'context' => $context]);
    }
}
