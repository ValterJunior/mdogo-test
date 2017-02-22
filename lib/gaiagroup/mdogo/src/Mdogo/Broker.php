<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core-entities
 */


/**
 * Message broker (i.e. event dispatcher)
 *
 * @package mdogo
 * @subpackage core-entities
 */
class Mdogo_Broker implements MdogoBrokerInterface
{
  /**
   * @var array
   */
  protected $callbacks = array();


  /**
   * Accepts arguments, registers broker as php * handler
   *
   * @param MdogoEnvironmentInterface $environment
   * @return Mdogo_Broker
   */
  public function __construct(MdogoEnvironmentInterface $environment)
  {
    set_error_handler(array($this, 'publishError'));
    set_exception_handler(array($this, 'publishException'));
    register_shutdown_function(array($this, 'publishShutdown'));

    foreach($environment->get('handlers') as $topic => $handlers) {
      foreach ((array) $handlers as $handler) {
        if (strinc($handler, ':')) {
          list($context, $callback) = explode(':', $handler);
        }
        else {
          list($context, $callback) = array(null, $handler);
        }
        $this->subscribe($topic, $callback, $context);
      }
    }
  }


  /**
   * Calls all callbacks subscribed to a topic
   *
   * @param string $topic
   * @param mixed $arguments (optional)
   * @throws MdogoException
   * @return void
   */
  public function publish($topic, $args = array())
  {
    if (!is_array($args) || 2 < func_num_args()) {
      $args = array_slice(func_get_args(), 1);
    }
    if (isset($this->callbacks[$topic])) {
      foreach($this->callbacks[$topic] as $callback) {
        $callback($args);
      }
    }
  }


  /**
   * Subscribes callback to topic
   *
   * @param string $topic
   * @param callback $callback
   * @param object $context (optional)
   * @return void
   */
  public function subscribe($topic, $callback, $context = null)
  {
    if (!isset($this->callbacks[$topic])) {
      $this->callbacks[$topic] = array();
    }
    $this->callbacks[$topic][] = new MdogoClosure($callback, $context);
  }


  /**
   * Unsubscribes callback(s) from topic
   *
   * @param string $topic
   * @param callable $callback (optional)
   * @param object $context (optional)
   * @return void
   */
  public function unsubscribe($topic, $callback = null, $context = null)
  {
    if (isset($this->callbacks[$topic])) {
      if (!empty($callback)) {
        $index = array_search(
          new MdogoClosure($callback, $context),
          $this->callbacks[$topic]
        );
        if (false !== $index) {
          array_unset($this->callbacks[$topic], $index);
        }
      }
      else {
        array_unset($this->callbacks, $topic);
      }
    }
  }


  /**
   * Publishes php errors as exceptions
   *
   * @handler
   *
   * @param int $errno
   * @param string $errstr
   * @param string $errfile
   * @param string $errline
   * @return void
   */
  public function publishError($errno, $errstr, $errfile, $errline)
  {
    if ($errno & error_reporting()) {
      $this->publish(
        'exception',
        new ErrorException($errstr, 0, $errno, $errfile, $errline)
      );
    }
  }


  /**
   * Publishes exceptions
   *
   * @handler
   *
   * @param Exception $exception
   * @return void
   */
  public function publishException(Exception $exception)
  {
    $this->publish('exception', $exception);
  }


  /**
   * Publishes shutdown, attempts to catch php errors
   *
   * @handler
   *
   * @return void
   */
  public function publishShutdown()
  {
    try {
      $this->publish('shutdown');
    }
    catch (Exception $exception) {
      $this->publishException($exception);
    }
    if ($e = error_get_last()) {
      $this->publishError($e['type'], $e['message'], $e['file'], $e['line']);
    }
  }
}
