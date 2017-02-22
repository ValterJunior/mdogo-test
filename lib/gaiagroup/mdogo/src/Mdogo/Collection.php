<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage core-objects
 */


/**
 * Abstract base collection
 *
 * @abstract
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
 * @method bool all($callable)
 * @method bool any($callable)
 * @method bool contains($value)
 * @method mixed find($callable)
 * @method mixed where($conditions)
 * @method mixed reduce($callable, $result)
 * @method array map($callable)
 * @method array walk($callable)
 * @method array diff($array)
 * @method array merge($array)
 * ...
 *
 * @package mdogo
 * @subpackage core-objects
 */
abstract class Mdogo_Collection extends MdogoArray implements MdogoCollectionInterface, Serializable
{
  /**
   * @var string
   */
  protected $model;

  /**
   * @var array
   */
  protected $arguments;


  /**
   * Sets up data
   *
   * @return Mdogo_Collection
   */
  public function __construct()
  {
    $this->model = $this->getModel();
    $this->immutable = false;

    if (func_num_args()) {
      call_user_func_array(array($this, 'load'), func_get_args());
    }
    else {
      $this->data = array();
    }
  }


  /**
   * Magically adds Mdogo entities
   *
   * @param string $name
   * @throws MdogoException
   * @return mixed
   */
  public function __get($name)
  {
    if (Mdogo::acknowledge($name)) {
      return Mdogo::get($name);
    }
    else {
      return parent::__get($name);
    }
  }


  /**
   * Magically adds Mdogo entities
   *
   * @param string $name
   * @return bool
   */
  public function __isset($name)
  {
    return Mdogo::acknowledge($name) || parent::__isset($name);
  }


  /**
   * Loads all records matched by condition as models
   *
   * @param array|string $argument1 (optional)
   * @param int|array $argument2 (optional)
   * @param string|null $argument3 (optional)
   * @return void
   */
  public function load()
  {
    if (func_num_args() && is_string(func_get_arg(0))) {
      $defaults = array(null, array());
      $method = 'loadWhere';
    }
    else {
      $defaults = array(array(), 1000, '');
      $method = 'loadAll';
    }
    $this->arguments = array_override($defaults, func_get_args());

    $this->data = call_user_func_array(
      array($this->model, $method),
      $this->arguments
    );
  }


  /**
   * Loads count of all records this collection represents
   *
   * @return int
   */
  public function count()
  {
    $method = is_string(reset($this->arguments)) ? 'countWhere' : 'countAll';

    return call_user_func_array(
      array($this->model, $method),
      $this->arguments
    );
  }


  /**
   * Exports models as csv, xml or html
   *
   * @param string $format
   * @param bool $header (optional)
   * @param string $delimiter (optional)
   * @param string $enclosure (optional)
   * @throws MdogoException
   * @return array
   */
  public function export($format = 'csv')
  {
    if (function_exists($func = 'export_'. $format)) {
      $args = array_slice(func_get_args(), 1);
      array_unshift($args, $this->toArray());
      return call_user_func_array($func, $args);
    }
    else {
      throw new MdogoException('invalid export format: '. $format);
    }
  }


  /**
   * Gets the model name to use
   *
   * @throws MdogoException
   * @return string
   */
  protected function getModel()
  {
    if (!empty($this->model)) {
      $model = $this->model;
    }
    else {
      $model = Mdogo_Model::getModel(
        Mdogo_Model::getTable(get_called_class())
      );
    }
    Mdogo::validate('model', $model);

    return $model;
  }


  /**
   * Implement Serializable
   */
  public function serialize()
  {
    $array = get_object_vars($this);
    $array['data'] = array_call($array['data'], 'toArray');

    return json_encode($array);
  }

  public function unserialize($json)
  {
    $class = $this->model;
    $array = json_decode($json, true);
    $array['data'] = array_map(
      function ($item) use ($class) {
        $model = new $class();
        foreach ($item as $key => $value) {
          $model->$key = $value;
        }
        return $model;
      },
      $array['data']
    );
    foreach ($array as $key => $value) {
      $this->$key = $value;
    }
  }
}
