<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core
 */


/**
 * Simple closure class with support for mdogo entities
 *
 * @package mdogo
 * @subpackage core
 */
class MdogoClosure implements MdogoClosureInterface
{
  /**
   * @var string|object
   */
  protected $obj;

  /**
   * @var string
   */
  protected $func;

  /**
   * @var array
   */
  protected $args;


  /**
   * Accepts dependencies
   *
   * @param string $func
   * @param string|object $obj (optional)
   * @param array $args (optional)
   * @return MdogoClosure
   */
  public function __construct($func, $obj = null, $args = array())
  {
    $this->func = $func;
    $this->obj = $obj;

    if (!is_array($args) || 3 < func_num_args()) {
      $args = array_slice(func_get_args(), 2);
    }
    $this->args = $args;
  }


  /**
   * Invokes enclosed function
   *
   * @param array $arguments (optional)
   * @param mixed ... (optional)
   * @return mixed
   */
  public function __invoke($args = array())
  {
    if (!is_array($args) || 1 < func_num_args()) {
      $args = func_get_args();
    }
    if (empty($this->obj)) {
      $callable = $this->func;
    }
    elseif (is_string($this->obj) && MdogoContainer::acknowledge($this->obj)) {
      $callable = array(MdogoContainer::get($this->obj), $this->func);
    }
    else {
      $callable = array($this->obj, $this->func);
    }
    return call_user_func_array($callable, array_merge($this->args, $args));
  }
}
