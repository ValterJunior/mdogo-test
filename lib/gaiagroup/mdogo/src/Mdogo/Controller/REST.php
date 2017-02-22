<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage controllers
 */


/**
 * Restful data access controller using dynamic Mdogo_Models
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
class Mdogo_Controller_REST extends Mdogo_Controller_Data
{
  /**
   * @var string
   */
  protected $model;

  /**
   * @var string
   */
  protected $type;

  /**
   * @var int
   */
  protected $uid;


  /**
   * Accepts request method/path
   *
   * @param string $method
   * @param string $path
   * @return void
   */
  public function bootstrap(&$method, &$path)
  {
    parent::bootstrap($method, $path);

    $this->type = sanitize(array_get($this->segments, 0));
    $this->uid = intval(array_get($this->segments, 1));
  }


  /**
   * Produces json response and returns it
   *
   * @return Mdogo_Response
   */
  public function respond()
  {
    $response = parent::respond();
    $response->ttl = -1;
    return $response;
  }


  /**
   * Resolves request to db model(s)
   *
   * @throws MdogoNotFoundException
   * @return void
   */
  protected function resolve()
  {
    if (empty($this->type)) {
      throw new MdogoNotFoundException();
    }
    if (!isset($this->model)) {
      $this->model = $this->getModel();
    }
    $this->process();
  }


  /**
   * Maps http verbs to controller methods
   *
   * @throws MdogoMethodNotAllowedException
   * @return void
   */
  protected function process()
  {
    switch ($this->method) {
      case 'POST'   : $this->body = $this->create(); break;
      case 'HEAD'   : $this->body = $this->read();   break;
      case 'GET'    : $this->body = $this->read();   break;
      case 'PUT'    : $this->body = $this->update(); break;
      case 'DELETE' : $this->body = $this->delete(); break;
      default:
        throw new MdogoMethodNotAllowedException();
    }
  }


  /**
   * Processes http get arguments for model::loadAll
   *
   * @return array
   */
  protected function processArguments()
  {
    $conditions = array();
    foreach ((array) $this->request->get('params', 'conditions') as $condition) {
      if (preg_match('/([^:]+)::(.*)/', $condition, $matches)) {
        $conditions[sanitize($matches[1])] = sanitize($matches[2]);
      }
    }
    $limit = $this->request->getInt('params', 'limit');
    $offset = $this->request->getInt('params', 'offset');
    if ($limit && $offset) {
      $limit = $offset .','. $limit;
    }
    if ($order = $this->request->getSafe('params', 'order')) {
      if ($sort = $this->request->getSafe('params', 'sort')) {
        $order .= preg_match('/d(esc)?/i', $sort) ? ' DESC' : '';
      }
    }
    return compact('conditions', 'limit', 'order');
  }


  /**
   * Returns filtered request body
   *
   * @return array
   */
  protected function processInput()
  {
    return $this->request->get('body') ?: array();
  }


  /**
   * Publishes request for authorization
   *
   * @param $method
   * @param mixed $model
   * @return void
   */
  protected function authorize($method, $model)
  {
    $this->broker->publish(
      'authorize',
      array(get_called_class(), $method, $model)
    );
  }


  /**
   * Creates one or more rows in db
   *
   * @throws MdogoForbiddenException
   * @return Mdogo_Model
   */
  protected function create()
  {
    if ($this->uid) {
      throw new MdogoForbiddenException();
    }
    else {
      $items = $this->processInput();
      if (!is_list($items)) {
        $items = array($items);
        $this->uid = true;
      }
      $validator = function ($item) {
        return is_array($item) && !array_get($item, 'uid');
      };
      if (!array_all($items, $validator)) {
        throw new MdogoForbiddenException();
      }
      $models = array();
      foreach ($items as $item) {
        /* @var Mdogo_Model $model */
        $model = new $this->model();
        foreach ($item as $key => $value) {
          $model->$key = $value;
        }
        $this->authorize(__FUNCTION__, $model);
        $model->save();
        array_push($models, $model);
      }
    }
    return $this->uid ? array_pop($models) : $models;
  }


  /**
   * Reads (or counts) one or more (or all) rows from db
   *
   * @throws MdogoNotFoundException
   * @return mixed
   */
  protected function read()
  {
    if ($this->uid) {
      $data = new $this->model($this->uid);
      if (!isset($data->uid)) {
        throw new MdogoNotFoundException();
      }
      $this->authorize(__FUNCTION__, $data);
    }
    else {
      if (null !== $this->request->get('params', 'count')) {
        $data = array('count' => call_user_func_array(
          array($this->model, 'countAll'),
          $this->processArguments()
        ));
        $this->authorize(__FUNCTION__, $data);
      }
      else {
        $data = call_user_func_array(
          array($this->model, 'loadAll'),
          $this->processArguments()
        );
        if (empty($data)) {
          throw new MdogoNotFoundException();
        }
        foreach ($data as $model) {
          $this->authorize(__FUNCTION__, $model);
        }
      }
    }
    return $data;
  }


  /**
   * Updates one or more rows in db
   *
   * @throws MdogoNotFoundException|MdogoForbiddenException
   * @return Mdogo_Model
   */
  protected function update()
  {
    if ($this->uid) {
      $items = array(
        array_merge(
          $this->processInput(),
          array('uid' => $this->uid)
        )
      );
    } else {
      $items = $this->processInput();
      $validator = function ($item) {
        return is_array($item) && array_get($item, 'uid');
      };
      if (!is_list($items) || !array_all($items, $validator)) {
        throw new MdogoForbiddenException();
      }
    }
    $models = array();
    foreach ($items as $item) {
      /* @var Mdogo_Model $model */
      $model = new $this->model(array_get($item, 'uid'));
      if (!isset($model->uid)) {
        throw new MdogoNotFoundException();
      }
      foreach ($item as $key => $value) {
        $model->$key = $value;
      }
      $this->authorize(__FUNCTION__, $model);
      $model->save();
      array_push($models, $model);
    }
    return $this->uid ? array_pop($models) : $models;
  }


  /**
   * Deletes one or more rows from db
   *
   * @throws MdogoNotFoundException|MdogoForbiddenException
   * @return bool
   */
  protected function delete()
  {
    if ($this->uid) {
      $items = array($this->uid);
    }
    else {
      $items = array_pluck($this->processInput(), 'uid');
    }
    foreach ($items as $item) {
      /* @var Mdogo_Model $model */
      $model = new $this->model($item);
      if (!isset($model->uid)) {
        throw new MdogoNotFoundException();
      }
      $this->authorize(__FUNCTION__, $model);
      $model->delete();
    }
    return true;
  }


  /**
   * Gets the model name to use
   *
   * @return string
   */
  protected function getModel()
  {
    /* @var Mdogo_Model $model */
    $model = Mdogo_Model::getModel($this->type);
    Mdogo::validate('model', $model);
    return $model;
  }
}
