<?php
class Example_Environment
{
  /**
   * Demonstrates environment/config usage
   */
  public function run()
  {
    $environment = Mdogo::getEnvironment();

    $resolve = $environment->resolve('routes', 'GET:check.json');

    if ($environment->keyExists('locale')
        && isset($environment['locale'])) {
      $array_access = $environment['locale'];
    }

    try {
      $environment['foo'] = 'bar';
    }
    catch (Exception $exception) {
      $exception = $exception->getMessage();
    }

    return get_defined_vars();
  }
}
