<?php defined('BASEPATH') OR exit('No direct script access allowed');

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * With LOG_CHANNEL=stderr, log_message() entries go to stderr as JSON lines
 * through Monolog, as container platforms expect; otherwise they go to
 * application/logs/ as usual. LOG_THRESHOLD applies in both cases.
 */
class MY_Log extends CI_Log
{
    /** @var Logger|null */
    private $stream = NULL;

    public function __construct()
    {
        parent::__construct();
        if (getenv('LOG_CHANNEL') === 'stderr' && class_exists(Logger::class)) {
            $handler = new StreamHandler('php://stderr');
            $handler->setFormatter(new JsonFormatter());
            $this->stream = new Logger('app', [$handler]);
        }
    }

    public function write_log($level, $msg)
    {
        if ($this->stream === NULL) {
            return parent::write_log($level, $msg);
        }
        $level = strtoupper((string) $level);
        // Same threshold rules as CI_Log::write_log().
        if (( ! isset($this->_levels[$level]) OR ($this->_levels[$level] > $this->_threshold))
            && ! isset($this->_threshold_array[$this->_levels[$level]])) {
            return FALSE;
        }
        $this->stream->log(strtolower($level) === 'all' ? 'debug' : strtolower($level), (string) $msg, ['environment' => ENVIRONMENT]);
        return TRUE;
    }
}
