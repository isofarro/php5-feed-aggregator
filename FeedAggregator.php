<?php

class FeedAggregator {
	// Configuration
	var $config = array(
		'datasource' => 'sqlite:/tmp/db-aggregator.db'
	);
	
	// Aggregator storage
	var $storage;
	
	// Feed Parser
	var $parser;

	/**
		__construct: initialising storage
		@param config array, an optional configuration hash
	**/
	public function __construct($config=false) {
		if ($config) {
			$this->setConfig($config);
		}
	}
	
	/**
		setConfig: updates the configuration with the supplied hash of configuration 
			settings.
		@param config array
	**/
	public function setConfig($config) {
		if (is_array($config)) {
			$this->config = array_merge($this->config, $config);
		}
	}

	/**
		getFeedItems: gets stored items from a particular feed
		@param URL of feed
		@param maximum number of items to retrieve (optional, default is 4)
		@returns Array of Entries in Simplified Atom Format
	**/
	public function getFeedItems($url, $maxItems=4) {
		$this->_initStorage();
		$items = $this->storage->getFeedEntries($url, $maxItems);
		return $items;
	}
	
	/**
		addFeed: subscribe to a new Feed in the aggregator
		@param a Feed Object
		@returns boolean whether the feed was successfully added or not
	**/
	public function addFeed($feed) {
		$this->_initStorage();
		return $this->storage->addFeed($feed);
	}
	
	/**
		updateFeeds: Update all the stored feeds in the aggregator
		@returns number of new items found
	**/
	public function updateFeeds() {
		$this->_initStorage();

		$feeds = $this->storage->getFeeds();
		//echo "Feeds: "; print_r($feeds);
		
		$itemsAdded = 0;
		foreach($feeds as $feed) {
			$itemsAdded += $this->updateFeed($feed);
		}
		return $itemsAdded;
	}
	
	/**
		updateFeed: Update a specified feed
		@param a Feed object or a URL of a feed
		@returns number of new items added
	**/
	public function updateFeed($feedInfo) {
		$feed = $this->_requestFeed($feedInfo);
		$itemsAdded = 0;
		//print_r($feed->entries[0]);
		
		$lastPolled  = time();
		$lastUpdated = 0;

		foreach($feed->entries as $entry) {
			//print_r($entry);
			
			$atom = $this->getSimplifiedAtomEntry($entry);
			//print_r($atom);
			$res = $this->storage->addFeedEntry($feedInfo, $atom);

			if ($res) {
				$itemsAdded++;
				//echo '+';
				
				$ts = strtotime($atom->published);
				if ($ts>$lastUpdated) {
					$lastUpdated = $ts;
				}
			}
		}

		// Update the Feed polling stats
		$feedInfo->lastPolled = $lastPolled;
		if ($lastUpdated) {
			$feedInfo->lastUpdated = $lastUpdated;
		}
		//print_r($feedInfo);
		$res = $this->storage->updateFeed($feedInfo);
		if (!$res) {
			echo "WARN: Something went wrong with the feed update.\n";
		}


		return $itemsAdded;
	}
	
	/**
		getSimplifiedAtomEntry: converts the given entry into a Simplifed Atom format,
			suitable for storage
		TODO: Move to FeedParser
		@param a complex Entry object
		@returns a simplified Atom version of the Entry
	**/
	public function getSimplifiedAtomEntry($entry) {
		$entryUrl = $this->getEntryUrl($entry);
		$atomEntry = (object)array(
			'title'     => $entry->title,
			'id'        => $entry->id,
			'url'       => $entryUrl,
			'author'    => $entry->authors[0],
			'summary'   => $entry->summary,
			'content'   => $entry->content,
			'published' => $entry->published,
			'updated'   => $entry->updated
		);
		return $atomEntry;
	}
	
	/**
		getEntryUrl: get the Entry URL for an Atom Entry. Looks through the links array
		TODO: Move to Feed Parser
		@param an Entry
		@returns the URL of the Entry, or NULL if not found.
	**/
	public function getEntryUrl($entry) {
		$entryRel = 'alternate';
		$entryType = 'text/html';
		
		$entryUrl = NULL;
		
		foreach($entry->links as $link) {
			if ($link->rel == $entryRel) {
				if ($link->type == $entryType) {
					$entryUrl = $link->href;
				}
			}
		}
		
		if (is_null($entryUrl)) {
			echo "WARN: Can't find an Entry URL in "; print_r($entry->links);
		}
		
		return $entryUrl;
	}
	
	
	
	##
	## Protected methods
	##
	
	protected function _requestFeed($feedInfo) {
		$this->_initStorage();
		$this->_initFeedParser();
		
		// Get the feed object if passed only a URL
		if (is_string($feedInfo)) {
			$feedInfo = $this->storage->getFeed($feedInfo);
			if (empty($feedInfo->id)) {
				echo "Feed {$feedInfo} doesn't exist in the Aggregator.\n";
				return NULL;
			}
		}
		
		//print_r($feedInfo);
		$feed = NULL;
		
		if (file_exists($feedInfo->url)) {
			//echo "INFO: Local feed data\n";
			$xml = file_get_contents($feedInfo->url);
			//echo strlen($xml) . " bytes.\n";
			$feed = $this->parser->parseXml($xml);
		} else {
			echo "INFO: Remote feed data\n";
			$feed = $this->parser->parse($feedInfo->url);
		}
		
		return $feed;
	}
	
	/**
		_initStorage: initialises storage object (FeedAggregatorPdoStorage)
	**/
	protected function _initStorage() {
		if(empty($this->storage)) {
			$this->storage = new FeedAggregatorPdoStorage($this->config);
		}
	}

	protected function _initFeedParser() {
		if(empty($this->parser)) {
			$this->parser = new FeedParser();
		}
	}

}

?>