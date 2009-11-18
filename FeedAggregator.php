<?php

class FeedAggregator {
	// Configuration
	var $config = array(
		'datasource' => 'sqlite:/tmp/db-aggregator.db'
	);
	
	// Aggregator storage
	var $storage;

	public function __construct($config=false) {
		if ($config) {
			$this->setConfig($config);
		}
	}
	
	public function setConfig($config) {
		$this->config = $config;
	}
	

	public function getItems($url, $maxItems=5) {
	
	}
	
	public function getFeed($url, $maxItems) {
	
	}
	
	
	##
	## Protected methods
	##
	
	protected function _initStorage() {
		if(empty($this->storage)) {
			$this->storage = new FeedAggregatorPdoStorage($this->config);
		}
	}

}

?>