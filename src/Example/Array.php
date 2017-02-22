<?php
class Example_Array
{
  /**
   * Demonstrates simple and immutable MdogoArrays
   */
  public function run()
  {
    $array1 = $array2 = array('foo' => 'baz', 'bar' => 'qux');

    $object1 = new MdogoArray($array1);
    $object2 = new MdogoArray($array2, true);

    $object1->set('yip', 'yap', array('bang', 'boom'));
    try {
      $object2->set('yip', 'yap', array('bang', 'boom'));
    }
    catch (Exception $exception2) {
      $exception2 = $exception2->getMessage();
    }

    if ($object1->has('yip', 'yap', 0)) {
      $object1->remove('yip', 'yap', 0);
    }

    $object1->ksort();

    $access = array(
      'array'     => $object2['foo'],
      'object'    => $object2->bar
    );

    return get_defined_vars();
  }
}
