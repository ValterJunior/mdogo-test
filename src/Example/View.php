<?php
class View extends Mdogo_View
{
  public function up($string)
  {
    return strtoupper($string);
  }
}

class Nested extends Mdogo_View
{
  public $sub;

  public function __construct($main = false, $sub = false)
  {
    parent::__construct($main);
    $this->sub = new Mdogo_View($sub);
  }
}

class Example_View
{
  /**
   * Demonstrates a simple Mdogo_View
   */
  public function run()
  {
    $view1 = new View('{{foo}} {{#up}}up?{{/up}}');
    $result1 = $view1->render(new Mdogo_Model_Example(1));

    $view2 = new View('{{foo}}-{{bar}} ');
    $result2 = $view2->render(new Mdogo_Collection_Example(array()));

    $view3 = new Nested('{{foo}} {{sub}}', '{{bar}}');
    $result3 = $view3->render(new Mdogo_Model_Example(1));

    return get_defined_vars();
  }
}
