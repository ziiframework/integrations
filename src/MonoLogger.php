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

    private bool $_withGlobalVars = false;
    public function withGlobalVars(bool $value): void
    {
        $this->_withGlobalVars = $value;
    }

    private ?Logger $_logger = null;

    private function logger(): Logger
    {
        if ($this->_logger !== null) {
            return $this->_logger;
        }

        $inflector = InflectorFactory::createForLanguage(Language::ENGLISH)->build();

        $uname = pf_posix_username('nobody');
        $filename = str_replace('_', '-', $inflector->tableize($this->_category));
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

    public function close(): void
    {
        $this->logger()->close();
    }

    private function build_internal_context($context): array
    {
        $result = [];

        if ($this->_withGlobalVars) {
            foreach ($GLOBALS as $k => $v) {
                $result["_$k"] = $v;
            }
        }

        $result['sessionId'] = $this->_sessionId;
        $result['context'] = $context;

        return $result;
    }

    /**
     * @param string|int|float $message
     * @param mixed|null $context
     * @return void
     */
    public function debug($message, $context = null): void
    {
        $this->logger()->debug('[' . $message . ']', $this->build_internal_context($context));
    }

    /**
     * @param string|int|float $message
     * @param mixed|null $context
     * @return void
     */
    public function info($message, $context = null): void
    {
        $this->logger()->info('[' . $message . ']', $this->build_internal_context($context));
    }

    /**
     * @param string|int|float $message
     * @param mixed|null $context
     * @return void
     */
    public function warning($message, $context = null): void
    {
        $this->logger()->warning('[' . $message . ']', $this->build_internal_context($context));
    }

    /**
     * @param string|int|float $message
     * @param mixed|null $context
     * @return void
     */
    public function error($message, $context = null): void
    {
        $this->logger()->error('[' . $message . ']', $this->build_internal_context($context));
    }
}
