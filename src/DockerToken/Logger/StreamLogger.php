<?php
/**
 * @author    Philip Bergman <pbergman@live.nl>
 * @copyright Philip Bergman
 */
namespace DockerToken\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

class StreamLogger extends AbstractLogger
{
    /** @var resource  */
    protected $stream;
    protected $levels = [
        LogLevel::ALERT,
        LogLevel::CRITICAL,
        LogLevel::DEBUG,
        LogLevel::EMERGENCY,
        LogLevel::ERROR,
        LogLevel::INFO,
        LogLevel::NOTICE,
        LogLevel::WARNING,
    ];

    /**
     * @param mixed $stream
     * @param null  $level
     */
    function __construct($stream, $level = null)
    {
        if (!is_null($level)) {
            $this->levels = $level;
        }

        $this->stream = $stream;
    }

    function __destruct()
    {
        if (!is_resource($this->stream)) {
            fclose($this->stream);
        }
    }


    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, array $context = array())
    {
        if (in_array($level, $this->levels)) {
            fwrite(
                $this->stream,
                sprintf("[%s] %-9s: %s\n", (new \DateTime())->format('D M d H:i:s Y'), strtoupper($level), $message)
            );
            fflush($this->stream);
        }
    }
}