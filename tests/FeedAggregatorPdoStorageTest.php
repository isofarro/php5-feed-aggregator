<?php

require_once dirname(dirname(__FILE__)) . '/FeedAggregatorPdoStorage.php';

class FeedAggregatorPdoStorageTest extends PHPUnit_Framework_TestCase {
	var $storage;
	var $dbPath = '/tmp/db-feed-aggregator-unittests.db';
	public function setUp() {
		if (file_exists($this->dbPath)) {
			unlink($this->dbPath);
		}
		$this->storage = new FeedAggregatorPdoStorage(array(
			'datasource' => "sqlite:{$this->dbPath}"
		));
	}
	
	public function tearDown() {
	}
	
	protected function _getFeed() {
		return (object)array(
			'title' => 'Example feed',
			'url'   => 'http://example.org/feed.xml'
		);
	}

	public function testInit() {
		$this->assertTrue(class_exists('FeedAggregatorPdoStorage'));
	}
	
	public function testGetFeeds() {
		$feeds = $this->storage->getFeeds();
		$this->assertNotNull($feeds);
		$this->assertTrue(is_array($feeds));
		$this->assertEquals(0, count($feeds));
				
		$feed = $this->_getFeed();

		$res = $this->storage->addFeed($feed);
		$this->assertTrue($res);

		$res = $this->storage->addFeed($feed);
		$this->assertFalse($res);

		$feeds = $this->storage->getFeeds();
		$this->assertNotNull($feeds);
		$this->assertTrue(is_array($feeds));
		$this->assertEquals(1, count($feeds));

		
	}
} 

?>