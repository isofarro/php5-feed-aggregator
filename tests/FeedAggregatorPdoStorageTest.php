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
		$feed = $this->_getFeed();

		// Check that the feed table is empty
		$feeds = $this->storage->getFeeds();
		$this->assertNotNull($feeds);
		$this->assertTrue(is_array($feeds));
		$this->assertEquals(0, count($feeds));

		// Check feed hasn't been added		
		$res = $this->storage->isFeed($feed->url);
		$this->assertFalse($res);

		// Adding a valid feed
		$res = $this->storage->addFeed($feed);
		$this->assertTrue($res);

		// Adding an existing feed
		$res = $this->storage->addFeed($feed);
		$this->assertFalse($res);

		// Check the feed table isn't empty
		$feeds = $this->storage->getFeeds();
		$this->assertNotNull($feeds);
		$this->assertTrue(is_array($feeds));
		$this->assertEquals(1, count($feeds));

		// Check returned feed is what we added
		$feed1 = $feeds[0];
		//print_r($feed1);

		$this->assertNotNull($feed1);
		$this->assertNotNull($feed1->id);
		$this->assertNotNull($feed1->url);
		$this->assertNotNull($feed1->title);
		$this->assertNotNull($feed1->type);
		$this->assertNotNull($feed1->status);
		$this->assertNotNull($feed1->created);
		$this->assertNotNull($feed1->lastUpdated);
		$this->assertNotNull($feed1->lastPolled);
		$this->assertNotNull($feed1->nextPoll);
		

		$this->assertTrue(is_numeric($feed1->id));
		$this->assertEquals($feed->url, $feed1->url);
		$this->assertEquals($feed->title, $feed1->title);
		$this->assertEquals('F', $feed1->type);
		$this->assertEquals('A', $feed1->status);
		$this->assertTrue(is_numeric($feed1->created));
		$this->assertEquals(0, $feed1->lastUpdated);
		$this->assertEquals(0, $feed1->lastPolled);
		$this->assertEquals(0, $feed1->nextPoll);

		// Check feed has been added		
		$res = $this->storage->isFeed($feed->url);
		$this->assertTrue($res);


		// Check feed can be retrieved		
		$feed2 = $this->storage->getFeed($feed->url);
		$this->assertNotNull($feed2);
		$this->assertNotNull($feed2->id);
		$this->assertNotNull($feed2->url);
		$this->assertNotNull($feed2->title);
		$this->assertNotNull($feed2->type);
		$this->assertNotNull($feed2->status);
		$this->assertNotNull($feed2->created);
		$this->assertNotNull($feed2->lastUpdated);
		$this->assertNotNull($feed2->lastPolled);
		$this->assertNotNull($feed2->nextPoll);
		

		$this->assertTrue(is_numeric($feed2->id));
		$this->assertTrue(is_numeric($feed2->created));
		$this->assertEquals($feed->url, $feed2->url);
		$this->assertEquals($feed->title, $feed2->title);
		$this->assertEquals('F', $feed2->type);
		$this->assertEquals('A', $feed2->status);
		$this->assertEquals(0,   $feed2->lastUpdated);
		$this->assertEquals(0,   $feed2->lastPolled);
		$this->assertEquals(0,   $feed2->nextPoll);
	}
} 

?>