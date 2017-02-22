<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core-entities
 */


/**
 * Request dispatcher / front controller
 *
 * @package mdogo
 * @subpackage core-entities
 */
class Mdogo_Dispatcher implements MdogoDispatcherInterface
{
  /**
   * @var MdogoEnvironmentInterface
   */
  protected $environment;

  /**
   * @var MdogoBrokerInterface
   */
  protected $broker;

  /**
   * @var MdogoCacheInterface
   */
  protected $cache;

  /**
   * @var string
   */
  protected $method;

  /**
   * @var string
   */
  protected $path;

  /**
   * @var string
   */
  protected $hash;


  /**
   * Accepts dependencies
   *
   * @param MdogoEnvironmentInterface $environment
   * @param MdogoBrokerInterface $broker
   * @param MdogoCacheInterface $cache
   * @return Mdogo_Dispatcher
   */
  public function __construct(MdogoEnvironmentInterface $environment, MdogoBrokerInterface $broker, MdogoCacheInterface $cache)
  {
    $this->environment = $environment;
    $this->broker = $broker;
    $this->cache = $cache;

    $this->broker->subscribe('exception', 'handleException', $this);
  }


  /**
   * Delivers error response for (first) exception
   *
   * @handler
   *
   * @param Exception $exception
   * @return void
   */
  public function handleException($exception)
  {
    $this->broker->unsubscribe('exception', 'handleException', $this);

    $controller = new Mdogo_Controller_Error($exception);
    $this->deliverResponse($controller->respond());
  }


  /**
   * Dispatches request processing
   *
   * @handler
   *
   * @param MdogoRequestInterface $request
   * @return void
   */
  public function handleRequest(MdogoRequestInterface $request)
  {
    $this->method = $request->getSafe('method');
    $this->path = $request->getSafe('path');
    $this->hash = $request->getSafe('hash');

    $this->broker->publish('rewrite', array(&$this->method, &$this->path));

    $this->authorizeRequest($request);

    ob_start();

    if (in_array($this->method, array('GET', 'HEAD'))) {
      $response = $this->getCachedResponse();
    }
    else {
      $response = $this->getResponse();
    }
    $this->broker->publish('response', $response);

    $this->deliverResponse($response);
  }


  /**
   * Checks csrf token
   *
   * @param MdogoRequestInterface $request
   * @throws MdogoForbiddenException
   * @return void
   */
  protected function authorizeRequest($request)
  {
    if ($this->environment->resolve('csrf', $this->method .':'. $this->path)) {
      $token = $request->getString('server', 'http_x_csrf_token');
      if (empty($token)) {
        $token = $request->getString('body', 'csrf_token');
      }
      if (!is_equal($token, Mdogo::get('session')->getToken())) {
        throw new MdogoForbiddenException('csrf token missing or invalid');
      }
    }
  }


  /**
   * Delivers response to client, terminates request
   *
   * @param MdogoResponseInterface $response
   * @return void
   */
  protected function deliverResponse(MdogoResponseInterface $response)
  {
    while(ob_get_level() && ob_end_clean());

    ob_start();
    $response->deliver();
    while(ob_get_level() && ob_end_flush());
    flush();

    if (function_exists('fastcgi_finish_request')) {
      fastcgi_finish_request();
    }
  }


  /**
   * Tries to find response in cache
   *
   * @return Mdogo_Response
   */
  protected function getCachedResponse()
  {
    $key = 'response:'. $this->hash;
    $response = $this->cache->get($key, $success);
    if (!$success) {
      $response = $this->getResponse();
      $this->cache->set($key, $response, $response->ttl ?: -1);
    }
    return $response;
  }


  /**
   * Produces actual response
   *
   * @throws MdogoNotImplementedException
   * @return Mdogo_Response
   */
  protected function getResponse()
  {
    $class = $this->environment->resolve('routes', $this->method .':'. $this->path);
    if (!$class) {
      throw new MdogoNotImplementedException('no matching route found');
    }
    /* @var Mdogo_Controller $controller */
    $controller = new $class();
    Mdogo::validate('controller', $controller);

    $this->broker->subscribe('preprocess', 'bootstrap', $controller);
    $this->broker->publish('preprocess', array(&$this->method, &$this->path));

    $response = $controller->respond();
    Mdogo::validate('response', $response);

    return $response;
  }
}
