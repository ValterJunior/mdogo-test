<?php
class Example_Broker
{
  /**
   * Demonstrates Mdogo's message broker
   */
  public function run()
  {
    $broker = Mdogo::getBroker();

    $result = array();
    $function = function() use (&$result) {
      $result[] = func_get_args();
    };

    $broker->subscribe('foo', $function);

    $broker->publish('foo', 'bar');
    $broker->publish('foo', 'baz', 'quux');

    $broker->unsubscribe('foo', $function);

    $broker->publish('foo', 'meep');

    return get_defined_vars();
  }
}
