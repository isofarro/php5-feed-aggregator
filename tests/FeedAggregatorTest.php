<?php

require_once dirname(dirname(__FILE__)) . '/FeedAggregator.php';
require_once dirname(dirname(__FILE__)) . '/FeedAggregatorPdoStorage.php';
require_once dirname(dirname(dirname(__FILE__))) . '/php5-feed-parser-and-normaliser/FeedParser.php';

class FeedAggregatorTest extends PHPUnit_Framework_TestCase {
	var $aggregator;
	var $dbFile = '/tmp/db-aggregator-unittest.db';
	
	public function setUp() {
		if (file_exists($this->dbFile)) {
			unlink($this->dbFile);
		}
		
		$this->aggregator = new FeedAggregator(array(
			'datasource' => "sqlite:{$this->dbFile}"
		));
	}
	
	protected function _getLocalFeed() {
		return (object)array(
			'title' => 'Isolani Web Articles',
			'url'   => dirname(__FILE__).'/data/articles.rdf'
			//'url'   => 'http://www.isolani.co.uk/articles/articles.rdf'
		);
	}

	public function testInit() {
		$this->assertTrue(class_exists('FeedAggregator'));
	}
	
	public function testUpdateFeeds() {
		$feed = $this->_getLocalFeed();
		
		// Test update of zero feeds gives zero new items
		$res = $this->aggregator->updateFeeds();
		$this->assertNotNull($res);
		$this->assertTrue(is_numeric($res));
		$this->assertEquals(0, $res);
		
		$res = $this->aggregator->addFeed($feed);
		$this->assertNotNull($res);
		$this->assertTrue($res);
		
		// Test update of zero feeds gives zero new items
		$res = $this->aggregator->updateFeeds();
		$this->assertNotNull($res);
		$this->assertTrue(is_numeric($res));
		$this->assertEquals(5, $res);
	}
	
	public function testGetFeedItems() {
		$feed = $this->_getLocalFeed();
		$res = $this->aggregator->addFeed($feed);
		$res = $this->aggregator->updateFeeds();
		$this->assertEquals(5, $res);

		$items = $this->aggregator->getFeedItems($feed->url);
		$this->assertNotNull($items);
		$this->assertType('array', $items);
		$this->assertEquals(4, count($items));
		
		$item = $items[0];
		$this->assertNotNull($item);
		//print_r($item);
		
		$this->assertNotNull($item->id);
		$this->assertNotNull($item->title);
		$this->assertNotNull($item->url);
		$this->assertNotNull($item->published);
		$this->assertNotNull($item->author);
		$this->assertNotNull($item->author->name);
	}
	
	
	
}

?>