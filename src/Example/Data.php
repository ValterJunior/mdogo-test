<?php
class Example_Data
{
  /**
   * Demonstrates php session usage
   */
  public function run()
  {
    $data = Mdogo::getData();

    $array_access = $data['example']['animals'][0];

    try {
      $data['foo'] = 'bar';
    }
    catch (Exception $exception) {
      $exception = $exception->getMessage();
    }

    $closure = $data->toClosure('get', 'example');
    $get = $closure('animals');

    return get_defined_vars();
  }
}
