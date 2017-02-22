<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core-entities
 */


/**
 * Data provider and json-to-array converter
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
 * @subpackage core-entities
 */
class Mdogo_Data extends MdogoArray implements MdogoDataInterface
{
  /**
   * @var Mdogo_Cache
   */
  protected $cache;

  /**
   * @var string
   */
  protected $basepath = 'dat';


  /**
   * Prepares data
   *
   * @param MdogoCacheInterface $cache
   * @return Mdogo_Data
   */
  public function __construct(MdogoCacheInterface $cache)
  {
    $this->cache = $cache;

    $data = $this->getData();
    parent::__construct($data, true);
  }


  /**
   * Gets data from json files
   *
   * @throws MdogoException
   * @return array
   */
  protected function getData()
  {
    $path = MDOGO_ROOT .'/'. $this->basepath;
    if (!is_dir($path)) {
      return array();
    }
    $key = 'data:'. $path;
    $data = $this->cache->get($key, $success, $mtime);
    if (!$success || $mtime < dirmtime($path, 'json', true)) {
      $data = dir_get_data($path, 'json', true);
      $this->cache->set($key, $data, 0);
    }
    return $data;
  }
}
