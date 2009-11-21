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

	protected function _getAuthor() {
		return (object)array(
			'name'  => 'Unit test author',
			'url'   => 'http://example.org/author.html',
			'email' => 'author@example.com'
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
		
		// Check feed can be retrieved by Id		
		$feed3 = $this->storage->getFeedById($feed2->id);
		$this->assertNotNull($feed3);
		$this->assertNotNull($feed3->id);
		$this->assertNotNull($feed3->url);
		$this->assertNotNull($feed3->title);

		$this->assertTrue(is_numeric($feed3->id));
		$this->assertTrue(is_numeric($feed3->created));
		$this->assertEquals($feed->url,      $feed3->url);
		$this->assertEquals($feed->title,    $feed3->title);
		$this->assertEquals($feed2->id,      $feed3->id);
		$this->assertEquals($feed2->created, $feed3->created);

		// Delete the feed
		$res = $this->storage->deleteFeed($feed->url);
		$this->assertTrue($res);

		// Check feed hasn't been added		
		$res = $this->storage->isFeed($feed->url);
		$this->assertFalse($res);
	}
	
	public function testAuthor() {
		$author = $this->_getAuthor();

		// Check that there are no authors
		$authors = $this->storage->getAuthors();
		$this->assertNotNull($authors);
		$this->assertTrue(is_array($authors));
		$this->assertEquals(0, count($authors));

		// Check the author hasn't already been added		
		$res = $this->storage->isAuthor($author->name);
		$this->assertFalse($res);

		// Adding an author
		$res = $this->storage->addAuthor($author);
		$this->assertTrue($res);
		
		// Check the author table isn't empty
		$authors = $this->storage->getAuthors();
		$this->assertNotNull($authors);
		$this->assertTrue(is_array($authors));
		$this->assertEquals(1, count($authors));
		
		$author1 = $authors[0];
		$this->assertNotNull($author1);
		$this->assertNotNull($author1->id);
		$this->assertNotNull($author1->name);
		$this->assertNotNull($author1->url);
		$this->assertNotNull($author1->email);
		
		$this->assertTrue(is_numeric($author1->id));
		$this->assertEquals($author1->url, $author1->url);
		$this->assertEquals($author1->name, $author1->name);
		$this->assertEquals($author1->email, $author1->email);

		// Check author has been added		
		$res = $this->storage->isAuthor($author->name);
		$this->assertTrue($res);


		// Check author can be retrieved		
		$author2 = $this->storage->getAuthor($author->name);
		$this->assertNotNull($author2);
		$this->assertNotNull($author2->id);
		$this->assertNotNull($author2->name);
		$this->assertNotNull($author2->url);
		$this->assertNotNull($author2->email);
		
		$this->assertTrue(is_numeric($author2->id));
		$this->assertEquals($author2->url, $author2->url);
		$this->assertEquals($author2->name, $author2->name);
		$this->assertEquals($author2->email, $author2->email);

		// Adding an existing author
		$res = $this->storage->addAuthor($author);
		$this->assertFalse($res);

		// Check author can be retrieved by id
		$author3 = $this->storage->getAuthorById($author2->id);
		$this->assertNotNull($author3);
		$this->assertNotNull($author3->id);
		$this->assertNotNull($author3->name);
		$this->assertNotNull($author3->url);
		$this->assertNotNull($author3->email);
		
		$this->assertTrue(is_numeric($author3->id));
		$this->assertEquals($author3->url, $author3->url);
		$this->assertEquals($author3->name, $author3->name);
		$this->assertEquals($author3->email, $author3->email);

		
		// Delete the author
		$res = $this->storage->deleteAuthor($author->name);
		$this->assertTrue($res);

		// Check author doesn't exist
		$res = $this->storage->isAuthor($author->name);
		$this->assertFalse($res);

	}
} 

?>