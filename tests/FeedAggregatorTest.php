<?php

require_once dirname(dirname(__FILE__)) . '/FeedAggregator.php';

class FeedAggregatorTest extends PHPUnit_Framework_TestCase {

	public function testInit() {
		$this->assertTrue(class_exists('FeedAggregator'));
	}
}

?>