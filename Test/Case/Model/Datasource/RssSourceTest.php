<?php
/**
 * Rss Datasource Test file
 */

App::uses('ConnectionManager', 'Model');
App::uses('DataSource', 'Model/Datasource');
App::uses('RssSource', 'Rss.Model/Datasource');

// Add new db config
ConnectionManager::create(
	'test_rss',
	array(
		'datasource' => 'Rss.RssSource',
		'feedUrl' => 'http://loadsys1.com/rss_datasource_test.rss',
		'encoding' => 'UTF-8',
	)
);

/**
 * Rss Testing Model
 *
 */
class RssModel extends CakeTestModel {

/**
 * Name of Model
 *
 * @var string
 */
	public $name = 'RssModel';

/**
 * Database Configuration
 *
 * @var string
 */
	public $useDbConfig = 'test_rss';

/**
 * Set recursive
 *
 * @var int
 */
	public $recursive = -1;
}

/**
 * Rss Datasource Test
 *
 */
class RssSourceTest extends CakeTestCase {

/**
 * Rss Source Instance
 *
 * @var RssSource
 */
	public $Model = null;

	public $channelAppend = array();

/**
 * Set up for Tests
 *
 * @return void
 */
	public function setUp() {
		parent::setUp();
		$this->Model = ClassRegistry::init('RssModel');
		$this->channelAppend = array(
			'title' => 'Test Feed for CakePHP RSS Datasource Unit Test',
			'link' => 'http://github.com/loadsys/CakePHP-RSS-Datasource',
			'description' => 'Test RSS feed for data source test',
		);

		return;
	}

/**
 * testFindAll
 *
 * @return void
 */
	public function testFindAll() {
		$result = $this->Model->find('all');
		$expected = array(
			array('RssModel' => array('title' => 'ATest1', 'description' => 'ATest1', 'link' => 'http://www.test1.com', 'pubDate'=>'Tue, 7 Sep 2010 00:01:01 -0500', 'channel' => $this->channelAppend)),
			array('RssModel' => array('title' => 'BTest2', 'description' => 'BTest2', 'link' => 'http://www.test2.com', 'pubDate'=>'Tue, 7 Sep 2010 00:01:01 -0500', 'channel' => $this->channelAppend)),
		);
		$this->assertEqual($result, $expected);

		return;
	}

/**
 * testFindLimit
 *
 * @return void
 */
	public function testFindLimit() {
		$result = $this->Model->find('all', array('limit' => 1));
		$expected = array(
			array('RssModel' => array('title' => 'ATest1', 'description' => 'ATest1', 'link' => 'http://www.test1.com', 'pubDate'=>'Tue, 7 Sep 2010 00:01:01 -0500', 'channel' => $this->channelAppend)),
		);
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('limit' => 1, 'page' => 2));
		$expected = array(
			array('RssModel' => array('title' => 'BTest2', 'description' => 'BTest2', 'link' => 'http://www.test2.com', 'pubDate'=>'Tue, 7 Sep 2010 00:01:01 -0500', 'channel' => $this->channelAppend)),
		);
		$this->assertEqual($result, $expected);

		return;
	}

/**
 * testFindOrder
 *
 * @return void
 */
	public function testFindOrder() {
		$result = $this->Model->find('all', array('order' => array('RssModel.title' => 'desc')));
		$expected = array(
						array('RssModel' => array('title' => 'BTest2', 'description' => 'BTest2', 'link' => 'http://www.test2.com', 'pubDate'=>'Tue, 7 Sep 2010 00:01:01 -0500', 'channel' => $this->channelAppend)),
						array('RssModel' => array('title' => 'ATest1', 'description' => 'ATest1', 'link' => 'http://www.test1.com', 'pubDate'=>'Tue, 7 Sep 2010 00:01:01 -0500', 'channel' => $this->channelAppend)),
		);
		$this->assertEqual($result, $expected);

		return;
	}

/**
 * testFindConditions
 *
 * @return void
 */
	public function testFindConditions() {
		$result = $this->Model->find('all', array('conditions' => array('RssModel.title' => 'ATest1')));
		$expected = array(array('RssModel' => array('title' => 'ATest1', 'description' => 'ATest1', 'link' => 'http://www.test1.com', 'pubDate'=>'Tue, 7 Sep 2010 00:01:01 -0500', 'channel' => $this->channelAppend)));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('RssModel.title =' => 'ATest1')));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('RssModel.title !=' => 'ATest1')));
		$expected = array(array('RssModel' => array('title' => 'BTest2', 'description' => 'BTest2', 'link' => 'http://www.test2.com', 'pubDate'=>'Tue, 7 Sep 2010 00:01:01 -0500', 'channel' => $this->channelAppend)));
		$this->assertEqual($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('RssModel.title' => 'ATest1', 'RssModel.description' => 'BTest2')));
		$expected = array();
		$this->assertIdentical($result, $expected);

		return;
	}

/**
 * testFindconditionsRecursive
 *
 * @return void
 */
	public function testFindConditionsRecursive() {
		$result = $this->Model->find('all', array('conditions' => array('AND' => array('RssModel.title' => 'ATest1', 'RssModel.description' => 'BTest2'))));
		$expected = array();
		$this->assertIdentical($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('OR' => array('RssModel.title' => array('ATest1', 'BTest2')))));
		$expected = array(
			array('RssModel' => array('title' => 'ATest1', 'description' => 'ATest1', 'link' => 'http://www.test1.com', 'pubDate'=>'Tue, 7 Sep 2010 00:01:01 -0500', 'channel' => $this->channelAppend)),
			array('RssModel' => array('title' => 'BTest2', 'description' => 'BTest2', 'link' => 'http://www.test2.com', 'pubDate'=>'Tue, 7 Sep 2010 00:01:01 -0500', 'channel' => $this->channelAppend)),
		);
		$this->assertIdentical($result, $expected);

		$result = $this->Model->find('all', array('conditions' => array('OR' => array('RssModel.title' => 'ATest1', 'RssModel.link' => 'http://www.test2.com'))));
		$expected = array(
			array('RssModel' => array('title' => 'ATest1', 'description' => 'ATest1', 'link' => 'http://www.test1.com', 'pubDate'=>'Tue, 7 Sep 2010 00:01:01 -0500', 'channel' => $this->channelAppend)),
			array('RssModel' => array('title' => 'BTest2', 'description' => 'BTest2', 'link' => 'http://www.test2.com', 'pubDate'=>'Tue, 7 Sep 2010 00:01:01 -0500', 'channel' => $this->channelAppend)),
		);
		$this->assertIdentical($result, $expected);

		return;
	}

/**
 * testFindFirst
 *
 * @return void
 */
	public function testFindFirst() {
		$result = $this->Model->find('first');
		$expected = array(
			'RssModel' => array(
				'title' => 'ATest1', 'description' => 'ATest1', 'link' => 'http://www.test1.com', 'pubDate'=>'Tue, 7 Sep 2010 00:01:01 -0500', 'channel' => $this->channelAppend
			),
		);
		$this->assertEqual($result, $expected);

		return;
	}

/**
 * testFindCount
 *
 * @return void
 */
	public function testFindCount() {
		$result = $this->Model->find('count');
		$this->assertEqual($result, 2);

		$result = $this->Model->find('count', array('limit' => 1));
		$this->assertEqual($result, 1);

		$result = $this->Model->find('count', array('limit' => 5));
		$this->assertEqual($result, 2);

		$result = $this->Model->find('count', array('limit' => 1, 'page' => 2));
		$this->assertEqual($result, 1);

		return;
	}

/**
 * testFindList
 *
 * @return void
 */
	public function testFindList() {
		$this->Model->primaryKey = 'title';
		$this->Model->displayField = 'title';
		$result = $this->Model->find('list');
		$expected = array('ATest1' => 'ATest1', 'BTest2' => 'BTest2');
		$this->assertEqual($result, $expected);

		return;
	}

}
