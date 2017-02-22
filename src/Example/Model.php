<?php
class Example_Model
{
  /**
   * Demonstrates a simple Mdogo_Model
   */
  public function run()
  {
    $model = new Mdogo_Model_Example(1);
    $array_access = $model['foo'];
    foreach($model as $key => $value) {
      $foreach[] = "$key: $value";
    }
    unset($key, $value);

    $models = Mdogo_Model_Example::loadAll();
    $count = Mdogo_Model_Example::countAll();

    return get_defined_vars();
  }
}
