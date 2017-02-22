<?php
class Example_Crypto
{
  /**
   * Demonstrates crypto functions
   */
  public function run()
  {
    $time = microtime(true);
    $bcrypt = bcrypt('password');
    $bcheck = bcheck('password', $bcrypt);
    $time = microtime(true) - $time;

    $random = random(8);
    $uuid = uuid();

    $is_bcrypt = is_bcrypt($bcrypt);
    $is_uuid = is_uuid($uuid);

    return get_defined_vars();
  }
}
