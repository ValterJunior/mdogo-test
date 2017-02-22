<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core-entities
 */


/**
 * Configurable logger and error handler
 *
 * @package mdogo
 * @subpackage core-entities
 */
class Mdogo_Logger implements MdogoLoggerInterface
{
  /**
   * @var MdogoEnvironment
   */
  protected $environment;

  /**
   * @var string
   */
  public $user = '-';


  /**
   * Accepts dependencies
   *
   * @param MdogoEnvironmentInterface $environment
   * @return Mdogo_Logger
   */
  public function __construct(MdogoEnvironmentInterface $environment)
  {
    $this->environment = $environment;
  }


  /**
   * Writes a message to a file in preconfigured log dir
   *
   * @param string $message
   * @param string $type (optional)
   * @return void
   */
  public function log($message, $type = 'debug')
  {
    $log_dir = $this->environment->get('log_dir');
    if ('syslog' === strtolower($log_dir)) {
      switch ($type) {
        case 'error': $priority = LOG_ERR; break;
        case 'debug': $priority = LOG_DEBUG; break;
        default:      $priority = LOG_INFO; break;
      }
      openlog('mdogo', LOG_ODELAY, LOG_LOCAL5);
      syslog($priority, $message);
      closelog();
    }
    else {
      mkdirp($log_dir);
      $file = "$log_dir/$type.log";
      file_put_contents($file, "$message\n", FILE_APPEND | LOCK_EX);
    }
  }


  /**
   * Replace Token in message and
   * write to a file in preconfigured log dir
   *
   * @param string $message
   * @param array $data
   * @param string $type
   * @return void
   */
  public function logf($message, $data = array(), $type = 'debug')
  {
    $this->log(replace_tokens($message, $data), $type);
  }


  /**
   * Composes error message, writes to error log
   *
   * @handler
   *
   * @param Exception $exception
   * @return void
   */
  public function handleException(Exception $exception)
  {
    $this->logf(
      $this->environment->get('error_log_format'),
      $this->getExceptionData($exception), 'error'
    );
  }


  /**
   * Initiates writing to {access|error} log
   *
   * @handler
   *
   * @return void
   */
  public function handleShutdown()
  {
    if ($this->environment->get('access_log') && 'cli' !== PHP_SAPI) {
      $this->logf(
        $this->environment->get('access_log_format'),
        $this->getRequestData(), 'access'
      );
    }
  }


  /**
   * Composes access log data
   *
   * @return array
   */
  protected function getRequestData()
  {
    $request = Mdogo::get('request');
    $response = Mdogo::get('response');

    $method = $request->get('server', 'request_method') ?: '-';
    $uri = $request->get('server', 'request_uri') ?: '_';

    return array(
      'agent'     => $request->get('server', 'http_user_agent') ?: '-',
      'client'    => $request->getClient($this->environment->get('anonymize_log')),
      'host'      => MDOGO_HOST,
      'method'    => $method,
      'referer'   => $request->get('server', 'http_referer') ?: '-',
      'request'   => $method .' '. $uri .' HTTP/1.1',
      'rid'       => $request->get('rid') ?: '-',
      'server'    => php_uname('n'),
      'size'      => $response->get('size') ?: 0,
      'status'    => $response->get('status') ?: 200,
      'time'      => date('d/M/Y:H:i:s O'),
      'uri'       => $uri,
      'user'      => $this->user
    );
  }


  /**
   * Composes error log data
   *
   * @param Exception $exception
   * @return array
   */
  protected function getExceptionData(Exception $exception)
  {
    $request = Mdogo::get('request');

    return array(
      'client'    => $request->getClient($this->environment->get('anonymize_log')),
      'error'     => get_class($exception),
      'file'      => str_replace(MDOGO_ROOT, 'MDOGO_ROOT', $exception->getFile()),
      'host'      => MDOGO_HOST,
      'line'      => $exception->getLine(),
      'message'   => $exception->getMessage(),
      'pid'       => getmypid(),
      'rid'       => $request->get('rid'),
      'server'    => php_uname('n'),
      'time'      => date('d/M/Y:H:i:s O'),
      'user'      => $this->user,
    );
  }
}
