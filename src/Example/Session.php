<?php
class Example_Session
{
  /**
   * Demonstrates session usage
   */
  public function run()
  {
    $session = Mdogo::getSession();

    $token = $session->getToken();

    $wrapped = &$session->toArray();
    $global = &$_SESSION;

    if (isset($session['foo'])) {
      $session['foo'] += 1;
    }
    else {
      $session['foo'] = 1;
    }

    return get_defined_vars();
  }
}
