<?php
class Controller extends Mdogo_Controller
{
  public function respond()
  {
    $this->response->setBody('Hello World', 'txt');
    return $this->response;
  }
}

class Example_Controller
{
  /**
   * Demonstrates a simple controller
   */
  public function run()
  {
    $controller = new Controller();
    $response = $controller->respond()->toArray();

    return get_defined_vars();
  }
}
