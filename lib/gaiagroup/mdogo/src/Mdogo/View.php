<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core-objects
 */


/**
 * Minimal mustache based view
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
 * @subpackage core-objects
 */
class Mdogo_View extends MdogoObject implements MdogoViewInterface
{
  /**
   * @var string
   */
  protected $template;


  /**
   * Sets up template string
   *
   * @param bool|string $template
   * @return Mdogo_View
   */
  public function __construct($template = false)
  {
    if (is_file($template)) {
      $this->template = file_get_contents($template);
    }
    elseif ($template) {
      $this->template = $template;
    }
  }


  /**
   * Renders template using supplied values
   *
   * @param mixed $values (optional)
   * @param array|bool $view (optional)
   * @return string
   */
  public function render($values = array(), $view = false)
  {
    $result = null;
    if (!empty($this->template)) {
      $view = $view ?: $this->toArray($values);
      if (is_object($values) && method_exists($values, 'toArray')) {
        $values = $values->toArray();
      }
      if (is_list($values)) {
        foreach ($values as $value) {
          $result .= $this->render($value, $view);
        }
      }
      else {
        $result = stache_render(
          $this->template,
          array_override($view, to_array_recursive($values))
        );
      }
    }
    return $result;
  }


  /**
   * Creates array of public properties and methods
   *
   * @param array $values (optional)
   * @return array
   */
  public function toArray($values = array())
  {
    $array = array_map(
      function ($var) use ($values) {
        switch (true) {
          case is_callable($var): return $var;
          case is_a($var, __CLASS__): return $var->render($values);
          case is_array($var) || is_object($var): return to_array_recursive($var);
          default: return $var;
        }
      },
      call_user_func('get_object_vars', $this)
    );
    foreach (call_user_func('get_class_methods', $this) as $method) {
      if ($method !== __FUNCTION__ && !strstt($method, '__')) {
        $array[$method] = array($this, $method);
      }
    }
    return $array;
  }
}
