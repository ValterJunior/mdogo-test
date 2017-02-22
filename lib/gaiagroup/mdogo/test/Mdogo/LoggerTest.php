<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage test
 */

class LoggerTest extends PHPUnit_Framework_TestCase
{
  protected $environment;

  protected $logger;

  private static $message = 'Folivora';

  private static $format_message = '@animal';

  private static $token = array(
    'animal' => 'tapir'
  );

  private static $request_data = array(
    'client'    => '127.0.0.1',
    'user'      => '-',
    'host'      => '-',
    'referer'   => '127.0.0.1/test',
    'request'   => 'GET / HTTP/1.1',
    'size'      => 128,
    'status'    => 200,
    'time'      => '27/May/2012:16:52:44 +0200',
    'agent'     => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_6_8) AppleWebKit/536.5 (KHTML, like Gecko) Chrome/19.0.1084.46 Safari/536.5'
  );

  private static $error_data = array(
    'time'      => '27/May/2012:16:54:33 +0200',
    'pid'       => '1337',
    'error'     => 'ErrorException',
    'message'   => 'Test Error Mesasge',
    'file'      => 'MDOGO_ROOT/lib/Mdogo/lib/Mdogo/Controller/File.php',
    'line'      => 12
  );


  protected function setUp()
  {
    $this->environment = Mdogo::get('environment');
    $this->logger      = Mdogo::get('logger');
    $this->log_dir     = $this->environment->get('log_dir');
  }


  public function testLog()
  {
    $file = $this->log_dir . '/test.log';
    $this->logger->log(LoggerTest::$message, 'test');

    $this->assertFileExists($file);
    $this->assertEquals(LoggerTest::$message . "\n", $this->tail($file));

    unlink($file);
  }


  public function testLogFormat()
  {
    $file = $this->log_dir . '/test.log';
    $this->logger->logf(LoggerTest::$format_message, LoggerTest::$token, 'test');

    $this->assertFileExists($file);
    $this->assertEquals(LoggerTest::$token['animal'] . "\n", $this->tail($file));

    unlink($file);
  }


  public function testAccess()
  {
    $file = $this->log_dir . '/access.log';
    $this->logger->handleShutdown();

    $this->assertFileExists($file);
  }


  public function testError()
  {
    $file = $this->log_dir . '/error.log';
    $this->logger->handleException(new Exception());

    $this->assertFileExists($file);
  }


  protected function tail($file)
  {
    return `tail -n 1 $file`;
  }
}
