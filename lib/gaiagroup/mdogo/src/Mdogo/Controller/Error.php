<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage controllers
 */


/**
 * Error controller
 *
 * @property MdogoEnvironment $environment
 * @property Mdogo_Broker     $broker
 * @property Mdogo_Cache      $cache
 * @property Mdogo_Data       $data
 * @property Mdogo_Database   $database
 * @property Mdogo_Logger     $logger
 * @property Mdogo_Request    $request
 * @property Mdogo_Response   $response
 * @property Mdogo_Session    $session
 *
 * @package mdogo
 * @subpackage controllers
 */
class Mdogo_Controller_Error extends Mdogo_Controller
{
  /**
   * @var int
   */
  protected $status;

  /**
   * @var Exception
   */
  protected $exception;


  /**
   * Sets status, exception
   *
   * @param Exception $exception
   * @return Mdogo_Controller_Error
   */
  public function __construct(Exception $exception = null)
  {
    $this->status = ($exception instanceof MdogoException) ? $exception->getCode() : 500;
    $this->exception = $exception;
  }


  /**
   * Prepares response object
   *
   * @return Mdogo_Response
   */
  public function respond()
  {
    $data = array(
      'title' => 'Error '. $this->status,
      'rid'   => $this->request->get('rid')
    );
    if ($this->exception && $this->environment->get('debug')) {
      $data['dump'] = $this->getDump($this->exception);
    }
    else {
      $data['body'] = $this->environment->get('status', $this->status) ?: 'Error';
    }
    $view = new Mdogo_View(static::getTemplate());
    $this->response->setBody($view->render($data), 'html', $this->status);
    return $this->response;
  }


  /**
   * Dumps exception to debug page
   *
   * @param Exception $exception
   * @return string
   */
  public static function getDump(Exception $exception)
  {
    $message = strip_tags($exception->getMessage());
    return array(
      'title' => get_class($exception) . ($message ? ': '. $message : ''),
      'trace' => str_replace(MDOGO_ROOT, '', $exception->getTraceAsString()),
      'file'  => str_replace(MDOGO_ROOT, '', $exception->getFile()),
      'line'  => $exception->getLine()
    );
  }


  /**
   * Gets html template for error page
   *
   * @return string
   */
  public static function getTemplate()
  {
    return <<<'EOT'
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>{{ title }}</title>
  <link rel="shortcut icon" href="about:blank">
</head>
<body style="font-family: Helvetica, sans-serif; color: #222;">
  <div style="width: 80%; margin: 1em auto; overflow: hidden;">
    <h1>{{ title }}</h1>
    <hr>
    {{#body}}<p>{{ body }}</p>{{/body}}
    {{#dump}}
      <h3>{{ title }}</h3>
      <h4>{{ file }}({{ line }})</h4>
      <pre>{{ trace  }}</pre>
    {{/dump}}
    <hr>
    <p><b>Request-ID:</b> {{ rid }}
    <hr>
  </div>
</body>
</html>
EOT;
  }
}
