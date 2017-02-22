<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core
 */


/**
 * Caching configuration container
 *
 * @final
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
final class MdogoEnvironment extends MdogoArray implements MdogoEnvironmentInterface
{
  /**
   * Determines configuration, configures php
   *
   * @return MdogoEnvironment
   */
  public function __construct()
  {
    $data = $this->getCachedConfig();

    foreach ($data['php'] as $key => $value) {
      ini_set($key, $value);
    }
    if ('cli' === PHP_SAPI) {
      foreach ($data['cli'] as $key => $value) {
        ini_set($key, $value);
      }
    }
    putenv('TMPDIR='. $data['cache_dir']);
    setlocale(LC_ALL, $data['locale']);
    date_default_timezone_set($data['timezone']);

    parent::__construct($data, true);
  }


  /**
   * Routes in property values using key
   *
   * @param string $property
   * @param string $string
   * @return mixed
   */
  public function resolve($property, $string)
  {
    $key = 'environment:resolve:'. $property .':'. $string;
    $data = MdogoCache::get($key, $success);
    if (!$success) {
      $data = route($string, $this->data[$property]);
      MdogoCache::set($key, $data, 0);
    }
    return $data;
  }


  /**
   * Gets cached configuration
   *
   * @return array
   */
  private function getCachedConfig()
  {
    $key = 'environment:local';
    $data = MdogoCache::get($key, $success);
    if (!$success) {
      $data = array_override($this->getDefaults(), $this->getConfig());
      MdogoCache::set($key, $data, $data['config_ttl']);
    }
    if (!defined('MDOGO_ENV')) {
      define('MDOGO_ENV', array_get($data, 'environment', 'DEFAULTS'));
    }
    return $data;
  }


  /**
   * Gets configuration from relevant ini files
   *
   * @return array
   */
  private function getConfig()
  {
    $configs = $this->getConfigs();

    if (defined('MDOGO_ENV')) {
      $env = MDOGO_ENV;
    }
    else {
      $hosts = array();
      foreach ($configs as $label => $config) {
        if (isset($config['hosts'])) {
          foreach ((array) $config['hosts'] as $host) {
            $hosts[$host] = $label;
          }
        }
      }
      $env = route(MDOGO_HOST, $hosts);
    }
    $data = array_get($configs, $env, array());

    while (array_key_exists('extends', $data)) {
      $label = $data['extends'];
      $data = array_override(
        array_without(array_get($configs, $label, array()), 'hosts'),
        array_without($data, 'extends')
      );
      array_unset($configs, $label);
    }
    return $data;
  }


  /**
   * Gets configurations from all ini files
   *
   * @return array
   */
  private function getConfigs()
  {
    $path = MDOGO_ROOT .'/cnf';

    $key = 'environment:global';
    $data = MdogoCache::get($key, $success, $mtime);
    if ($mtime < dirmtime($path, 'ini')) {
      $success = false;
      MdogoCache::initialize(time());
    }
    if (!$success) {
      $data = dir_get_data($path, 'ini');
      foreach ($data as $label => &$config) {
        $config += array('environment' => $label);
      }
      MdogoCache::set($key, $data, 0);
    }
    return $data;
  }


  /**
   * Gets default configuration
   *
   * @return array
   */
  private function getDefaults()
  {
    $key = 'environment:defaults';
    $data = MdogoCache::get($key, $success);
    if (!$success) {
      $data = parse_ini_file(realpath(__DIR__ .'/../DEFAULTS.ini'), true);
      MdogoCache::set($key, $data, 0);
    }
    return $data;
  }
}
