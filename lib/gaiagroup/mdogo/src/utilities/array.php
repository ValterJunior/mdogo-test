<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage utilities
 */


/**
 * Checks if all array items evaluate to true
 *
 * @param array $array
 * @param bool|callable $callable $callable (optional)
 * @return bool
 */
function array_all(array $array, $callable = false)
{
  $callable = $callable ?: function ($value) { return !!$value; };
  foreach ($array as $value) {
    if (!call_user_func($callable, $value)) {
      return false;
    }
  }
  return true;
}


/**
 * Checks if any array item evaluates to true
 *
 * @param array $array
 * @param bool|callable $callable $callable (optional)
 * @return bool
 */
function array_any(array $array, $callable = false)
{
  $callable = $callable ?: function ($value) { return !!$value; };
  foreach ($array as $value) {
    if (call_user_func($callable, $value)) {
      return true;
    }
  }
  return false;
}



/**
 * Calls method on every value in array
 *
 * @param array $array
 * @param string $method
 * @param array $arguments (optional)
 * @return array
 */
function array_call(array $array, $method, $arguments = array())
{
  foreach ($array as &$value) {
    if (is_object($value) && method_exists($value, $method)) {
      $value = call_user_func_array(array($value, $method), $arguments);
    }
  }
  return $array;
}



/**
 * Removes all elements from array
 *
 * @param array &$array
 * @return void
 */
function array_clear(array &$array)
{
  foreach (array_reverse(array_keys($array)) as $key) {
    array_unset($array, $key);
  }
}



/**
 * Checks if values are present in array
 *
 * @param array $array
 * @param mixed $values
 * @return bool
 */
function array_contains(array $array, $values)
{
  return array_empty(array_diff((array) $values, array_values($array)));
}



/**
 * Checks if keys are present in array
 *
 * @param array $array
 * @param mixed $values
 * @return bool
 */
function array_contains_keys(array $array, $values)
{
  return array_empty(array_diff((array) $values, array_keys($array)));
}



/**
 * Checks if array is empty
 *
 * @param array $array
 * @return bool
 */
function array_empty(array $array)
{
  // 'empty' only works on variables...
  return empty($array);
}



/**
 * Filters array using its keys
 *
 * @param array $array
 * @param callable $callable
 * @return array
 */
function array_filter_keys(array $array, $callable)
{
  return array_intersect_key(
    $array,
    array_flip(array_filter(array_keys($array), $callable))
  );
}



/**
 * Finds item in array using callable
 *
 * @param array $array
 * @param bool|callable $callable (optional)
 * @return mixed
 */
function array_find(array $array, $callable = false)
{
  $callable = $callable ?: function ($value) { return !!$value; };
  foreach ($array as $value) {
    if (call_user_func($callable, $value)) {
      return $value;
    }
  }
  return false;
}



/**
 * Returns first value from array
 *
 * @param array $array
 * @return mixed
 */
function array_first(array $array)
{
  // 'reset' only works on variables...
  return reset($array);
}



/**
 * Gets value or default from array
 *
 * @param array $array
 * @param string $name(optional)
 * @param mixed $default (optional)
 * @return mixed
 */
function array_get(array $array, $name, $default = null)
{
  return isset($array[$name]) ? $array[$name] : $default;
}



/**
 * Inserts addition(s) into array at pos
 *
 * @param array $array
 * @param mixed $pos
 * @param array $addition
 * @return array
 */
function array_insert(array $array, $pos, $addition)
{
  $offset = array_get(array_offsets($array), $pos);

  $values = array_values($array);
  array_splice($values, $offset, 0, array_values($addition));

  if (is_list($array)) {
    return $values;
  }
  else {
    $keys = array_keys($array);
    array_splice($keys, $offset, 0, array_keys($addition));

    return array_combine($keys, $values);
  }
}



/**
 * Returns last value from array
 *
 * @param array $array
 * @return mixed
 */
function array_last(array $array)
{
  // 'end' only works on variables...
  return end($array);
}



/**
 * Returns an associative array: key => offset
 *
 * @param array $array
 * @return array
 */
function array_offsets(array $array)
{
  return array_flip(array_keys($array));
}



/**
 * Really merges two arrays recursively
 *
 * @param array $array
 * @param array $override
 * @return array
 */
function array_override(array $array, array $override = array())
{
  foreach ($override as $key => $value) {
    if (is_array($value) && isset($array[$key]) && is_array($array[$key])) {
      $array[$key] = array_override($array[$key], $value);
    }
    else {
      $array[$key] = $value;
    }
  }
  return $array;
}



/**
 * Returns a list of $name properties
 *
 * @param array $array
 * @param string $name
 * @return array
 */
function array_pluck(array $array, $name)
{
  $result = array();
  foreach ($array as $key => $value) {
    if (!is_array($value)) {
      $value = (array) $value;
    }
    $result[$key] = array_get($value, $name, false);
  }
  return $result;
}



/**
 * Removes elements from array
 *
 * @param array &$array
 * @param string $key
 * @return void
 */
function array_unset(array &$array, $key)
{
  if (array_key_exists($key, $array)) {
    $offsets = array_offsets($array);
    array_splice($array, $offsets[$key], 1);
  }
}



/**
 * Filters array and only returns elements matching condition
 *
 * @param array $array
 * @param array $conditions (optional)
 * @return array
 */
function array_where(array $array, $conditions = array())
{
  return array_filter($array, function ($item) use ($conditions) {
    if (is_object($item) || is_array($item)) {
      $item = (array) $item;
      foreach ($conditions as $key => $value) {
        if (!array_key_exists($key, $item) || $item[$key] != $value) {
          return false;
        }
      }
      return true;
    }
    return false;
  });
}



/**
 * Returns array without values specified by key(s)
 *
 * @param array $array
 * @param array $keys
 * @return array
 */
function array_without(array $array, $keys)
{
  foreach ((array) $keys as $key) {
    array_unset($array, $key);
  }
  return $array;
}



/**
 * Traverses array using provided arguments
 *
 * @param array &$array
 * @param array $arguments
 * @return mixed
 */
function &array_get_recursive(array &$array, array $arguments)
{
  $result = $array;
  foreach ((array) $arguments as $argument) {
    if (is_array($result) && isset($result[$argument])) {
      $item = &$result[$argument];
      if (is_object($item) && method_exists($item, 'toArray')) {
        $item = &$item->toArray();
      }
      $result = &$item;
    }
    else {
      $result = null;
    }
  }
  return $result;
}



/**
 * Traverses array using provided arguments and checks if key exists
 *
 * @param array &$array
 * @param array $arguments
 * @return bool
 */
function array_isset_recursive(array &$array, array $arguments)
{
  $argument = array_shift($arguments);
  if (isset($array[$argument])) {
    $item = &$array[$argument];
    if (is_object($item) && method_exists($item, 'toArray')) {
      $item = &$item->toArray();
    }
    if (empty($arguments)) {
      return true;
    }
    elseif (is_array($item)) {
      return array_isset_recursive($item, $arguments);
    }
    else {
      return false;
    }
  }
  else {
    return false;
  }
}



/**
 * Traverses array using provided arguments and sets value
 *
 * @param array &$object
 * @param array $arguments (optional)
 * @param mixed $value (optional)
 * @return void
 */
function array_set_recursive(array &$array, array $arguments, $value = true)
{
  $argument = array_shift($arguments);
  if (0 === count($arguments)) {
    $array[$argument] = $value;
  }
  else {
    if (!isset($array[$argument])) {
      $array[$argument] = array();
    }
    $item = &$array[$argument];
    if (is_object($item) && method_exists($item, 'toArray')) {
      $item = &$item->toArray();
    }
    elseif (!is_array($item)) {
      $item = array();
    }
    array_set_recursive($item, $arguments, $value);
  }
}



/**
 * Traverses array using provided arguments and removes value
 *
 * @param array &$array
 * @param array $arguments
 * @return void
 */
function array_unset_recursive(array &$array, array $arguments)
{
  $argument = array_shift($arguments);
  if (isset($array[$argument])) {
    $item = &$array[$argument];
    if (is_object($item) && method_exists($item, 'toArray')) {
      $item = &$item->toArray();
    }
    if (is_array($item) && 0 < count($arguments)) {
      array_unset_recursive($item, $arguments);
    }
    else {
      $item = null;
      array_unset($array, $argument);
    }
  }
}



/**
 * Converts anything to a (nested) array(s)
 *
 * @param mixed $input (optional)
 * @return array
 */
function to_array_recursive($input = array())
{
  if (1 < func_num_args()) {
    $input = func_get_args();
  }
  return json_decode(json_encode((array) $input), true);
}
