<?php
/**
 * @copyright 2014 GAIA AG, Hamburg
 * @license https://raw.github.com/gaiagroup/mdogo/master/LICENSE.md
 * @package mdogo
 * @subpackage test
 */

class ModelTest extends PHPUnit_Framework_TestCase
{
  const VALUE = 'narwhal';

  protected $class = 'Mdogo_Model_Test';


  public static function tearDownAfterClass()
  {
    $db = Mdogo::get('database')->open('w');
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    if ('mysql' === $driver) {
      $db->exec('TRUNCATE TABLE test;');
    }
    elseif ('sqlite' === $driver) {
      $db->exec('DELETE FROM test;');
    }
  }


  public function testGenerate()
  {
    $this->assertTrue(class_exists($this->class));
    return;
  }

  /**
   * @depends testGenerate
   */
  public function testCreate()
  {
    $this->assertInstanceOf($this->class, new $this->class());
    return;
  }

  /**
   * @depends testCreate
   */
  public function testSave()
  {
    $model = new $this->class();
    $model->value = self::VALUE;
    $model->save();

    $this->assertObjectHasAttribute('uid', $model);

    return $model->uid;
  }

  /**
   * @depends testSave
   */
  public function testConstruct($uid)
  {
    $model = new $this->class($uid);

    $this->assertObjectHasAttribute('value', $model);
    $this->assertEquals($uid, $model->uid);
    $this->assertEquals(self::VALUE, $model->value);

    return;
  }

  /**
   * @depends testSave
   */
  public function testUpdate($uid)
  {
    $model = new $this->class($uid);
    $model->save(array('value' => 'sloth'));

    $this->assertObjectHasAttribute('value', $model);
    $this->assertEquals($uid, $model->uid);
    $this->assertEquals('sloth', $model->value);

    $model->save(array('value' => self::VALUE));

    return $uid;
  }

  /**
   * @depends testUpdate
   */
  public function testLoad($uid)
  {
    $model = new $this->class();
    $model->load(array('uid' => $uid));

    $this->assertObjectHasAttribute('value', $model);
    $this->assertEquals($uid, $model->uid);
    $this->assertEquals(self::VALUE, $model->value);

    return $uid;
  }

  /**
   * @depends testUpdate
   */
  public function testLoadAll($uid)
  {
    $class = $this->class;
    $models = $class::loadAll();

    $this->assertTrue(is_array($models));
    $this->assertEquals($uid, $models[0]->uid);
    $this->assertEquals(self::VALUE, $models[0]->value);

    return $uid;
  }

  /**
   * @depends testLoadAll
   */
  public function testLoadCount($uid)
  {
    $class = $this->class;

    $this->assertEquals(1, $class::countAll());

    return $uid;
  }

  /**
   * @depends testLoadCount
   */
  public function testDelete($uid)
  {
    $model = new $this->class($uid);
    $model->delete();

    $model = new $this->class($uid);
    $this->assertEmpty($model->uid);
  }
}
