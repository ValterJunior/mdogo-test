<?php
class Example_Collection
{
  /**
   * Demonstrates a simple Mdogo_Collection
   */
  public function run()
  {
    $collection = new Mdogo_Collection_Example(array());
    $count = $collection->count();
    $array_access = $collection[0]['foo'];
    $func_access = $collection->get(0, 'foo');

    $json = $collection->serialize();
    $serialized = new Mdogo_Collection_Example();
    $serialized->unserialize($json);
    $result = $serialized == $collection;

    $where = new Mdogo_Collection_Example(
      'foo = :foo', array('foo' => 'threeFoo')
    );
    $where = $where->toArray();

    return get_defined_vars();
  }
}
