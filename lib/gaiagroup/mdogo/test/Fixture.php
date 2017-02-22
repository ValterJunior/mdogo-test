<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage test
 */


// Access private/protected object properties
function trespass(&$object, $property_name, $value = null)
{
  $property = new ReflectionProperty($object, $property_name);
  $property->setAccessible(true);
  if (!is_null($value)) {
    $result = $property->setValue($object, $value);
  }
  else {
    $result = $property->getValue($object);
  }
  $property->setAccessible(false);
  return $result;
}


// Force execution of object method
function coerce(&$object, $method_name)
{
  $method = new ReflectionMethod($object, $method_name);
  $method->setAccessible(true);
  $arguments = array_slice(func_get_args(), 2);
  if (empty($arguments)) {
    $result = $method->invoke($object);
  }
  else {
    $result = $method->invokeArgs($object, $arguments);
  }
  $method->setAccessible(false);
  return $result;
}


// Bootstrap mdogo into default environment
call_user_func(function () {
  define('MDOGO_ENV', 'test');
  require realpath(__DIR__ .'/../mdogo.php');
  Mdogo::bootstrap();

  $data = array_override(
    parse_ini_file(realpath(__DIR__ .'/../DEFAULTS.ini'), true),
    parse_ini_file(realpath(__DIR__ .'/test.ini'), true)
  );
  $data['db'] = 'sqlite:'. realpath(__DIR__ .'/test.sqlite3');
  $environment = Mdogo::get('environment');
  trespass($environment, 'data', $data);

  MdogoContainer::initialize($environment);
  Mdogo::publish('bootstrap-test');
});
