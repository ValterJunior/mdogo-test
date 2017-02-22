<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core
 */


/**
 * Base class adding array methods and array-like behavior
 *
 * @method bool all($callable)
 * @method bool any($callable)
 * @method bool contains($value)
 * @method mixed find($callable)
 * @method mixed where($conditions)
 * @method mixed reduce($callable, $result = null)
 * @method array map($callable)
 * @method array walk($callable)
 * @method array diff($array)
 * @method array merge($array)
 * ...
 *
 * @package mdogo
 * @subpackage core
 */
class MdogoArray implements MdogoArrayInterface, ArrayAccess, Countable, IteratorAggregate
{
  /**
   * @var array
   */
  protected $data;

  /**
   * @var bool
   */
  protected $immutable;


  /**
   * Accepts array
   *
   * @param array &$data (optional)
   * @param bool $immutable (optional)
   * @return MdogoArray
   */
  public function __construct(array &$data = array(), $immutable = false)
  {
    $this->data = &$data;
    $this->immutable = !!$immutable;
  }


  /**
   * Makes standard (array) functions work on "array" data
   * Uses a copy of data if this object is immutable
   *
   * @param string $name
   * @param array $arguments
   * @throws MdogoException
   * @return mixed
   */
  public function __call($name, $arguments)
  {
    if ('unset' === $name) {
      return call_user_func_array(array($this, 'remove'), $arguments);
    }
    if (!function_exists($name)) {
      $name = 'array_'. uncamelize($name);
    }
    if (function_exists($name)) {
      $offset = intval(in_array(
        $name, array('array_map', 'array_search', 'array_key_exists')
      ));
      $array = &$this->toArray();
      array_splice($arguments, $offset, 0, array(&$array));

      return call_user_func_array($name, $arguments);
    }
    else {
      throw new MdogoException('method not found: '. $name);
    }
  }


  /**
   * Traverses "array" data using provided arguments and checks if key exists
   *
   * @param array $arguments
   * @param ...   $argument (optional)
   * @return mixed
   */
  public function has($arguments)
  {
    if (!is_array($arguments) || 1 < func_num_args()) {
      $arguments = func_get_args();
    }
    return array_isset_recursive($this->data, $arguments);
  }


  /**
   * Traverses "array" data using provided arguments
   *
   * @param array $arguments
   * @param ...   $argument (optional)
   * @return mixed
   */
  public function get($arguments)
  {
    if (!is_array($arguments) || 1 < func_num_args()) {
      $arguments = func_get_args();
    }
    return array_get_recursive($this->data, $arguments);
  }


  /**
   * Traverses "array" data using provided arguments and sets value
   *
   * @param array $arguments
   * @param ...   $argument (optional)
   * @param mixed $value
   * @throws MdogoException
   * @return mixed
   */
  public function set($arguments, $value)
  {
    if (!is_array($arguments) || 2 < func_num_args()) {
      $arguments = func_get_args();
      $value = array_pop($arguments);
    }
    if ($this->isImmutable()) {
      throw new MdogoException('trying to change immutable array');
    }
    array_set_recursive($this->data, $arguments, $value);
  }


  /**
   * Traverses "array" data using provided arguments and removes value
   *
   * @param array $arguments
   * @param ...   $argument (optional)
   * @throws MdogoException
   * @return void
   */
  public function remove($arguments)
  {
    if (!is_array($arguments) || 1 < func_num_args()) {
      $arguments = func_get_args();
    }
    if ($this->isImmutable()) {
      throw new MdogoException('trying to change immutable array');
    }
    array_unset_recursive($this->data, $arguments);
  }


  /**
   * Checks if this "array" is immutable
   *
   * @return bool
   */
  public function isImmutable()
  {
    return $this->immutable;
  }


  /**
   * Returns "array" data (or a copy thereof) by reference
   *
   * @return array
   */
  public function &toArray()
  {
    if ($this->isImmutable()) {
      $data = $this->data;
    }
    else {
      $data = &$this->data;
    }
    return $data;
  }


  /**
   * Returns object method as closure
   *
   * @param string $name
   * @param mixed ... (optional)
   * @return MdogoClosureInterface
   */
  public function toClosure($name)
  {
    if (!call_user_func('is_callable', array($this, $name))) {
      throw new MdogoException('unknown/private method: '. $name);
    }
    return new MdogoClosure($name, $this, array_slice(func_get_args(), 1));
  }


  /**
   * Implement ArrayAccess (and "ObjectAccess")
   */
  public function offsetExists($name) { return $this->has($name); }
  public function __isset($name) { return $this->has($name); }

  public function offsetGet($name) { return $this->get($name); }
  public function __get($name) { return $this->get($name); }

  public function offsetSet($name, $value) { return $this->set($name, $value); }
  public function __set($name, $value) { return $this->set($name, $value); }

  public function offsetUnset($name) { return $this->remove($name); }
  public function __unset($name) { return $this->remove($name); }


  /**
   * Implements Countable
   */
  public function count()
  {
    return count($this->data);
  }


  /**
   * Implements IteratorAggregate
   */
  public function getIterator()
  {
    return new ArrayIterator($this->toArray());
  }
}
