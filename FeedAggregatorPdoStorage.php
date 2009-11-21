<?php

class FeedAggregatorPdoStorage {
	// Database connection
	var $db;

	// Database schema
	var $schema;
	
	// Prepared statement cache
	var $stmCache = array();
	
	// Configuration
	var $config = array(
		'datasource' => 'sqlite:/tmp/tmp-feed-aggregator.db'
	);
	
	
	/**
		__construct: initialising storage
		@param config array, an optional configuration hash
	**/
	public function __construct($config=false) {
		if ($config) {
			$this->setConfig($config);
		}
	}
	
	public function setConfig($config) {
		if (is_array($config)) {
			$this->config = array_merge($this->config, $config);
		}
	}


	public function addFeed($feed) {
		$this->_initDbConnection();
		
		$stm = $this->_prepareStatement('feed', 'add');
		$stm->execute(array(
			':url'         => $feed->url,
			':title'       => $feed->title,

			':type'        => (!empty($feed->type))?$feed->type:'F',
			':status'      => (!empty($feed->status))?$feed->status:'A',
			':created'     => time(),
			':lastUpdated' => 0,
			':lastPolled'  => 0,
			':nextPoll'    => 0
		));

		if ($this->_isPdoError($stm)) {
			return false;
		}		
		
		return true;
	}

	public function getFeeds() {
		$this->_initDbConnection();
		
		$stm = $this->_prepareStatement('feed', 'getAll');		
		$stm->execute();
		
		if ($this->_checkPdoError($stm)) {
			return NULL;
		}

		$feeds = array();
		while($row = $stm->fetchObject()) {
			$feeds[] = $row;
		}
		return $feeds;
	}

	public function isFeed($url) {
		$this->_initDbConnection();
		
		$stm = $this->_prepareStatement('feed', 'getByUrl');		
		$stm->execute(array(
			':url' => $url
		));
		
		if ($this->_checkPdoError($stm)) {
			return false;
		}

		if ($feed = $stm->fetchObject()) {
			return true;
		}	
		return false;
	}

	public function getFeed($url) {
		$this->_initDbConnection();
		
		$stm = $this->_prepareStatement('feed', 'getByUrl');		
		$stm->execute(array(
			':url' => $url
		));
		
		if ($this->_checkPdoError($stm)) {
			return false;
		}

		if ($feed = $stm->fetchObject()) {
			return $feed;
		}	
		return NULL;
	}

	public function getFeedById($id) {
		$this->_initDbConnection();
		
		$stm = $this->_prepareStatement('feed', 'getById');		
		$stm->execute(array(
			':id' => $id
		));
		
		if ($this->_checkPdoError($stm)) {
			return false;
		}

		if ($feed = $stm->fetchObject()) {
			return $feed;
		}	
		return NULL;
	}

	public function deleteFeed($feed) {
		$this->_initDbConnection();
		
		if (is_string($feed)) {
			$stm = $this->_prepareStatement('feed', 'deleteByUrl');		
			$stm->execute(array(
				':url' => $feed
			));
		} else if (!empty($feed->id)) {
			$stm = $this->_prepareStatement('feed', 'deleteById');		
			$stm->execute(array(
				':id' => $feed->id
			));
		}
			
		if ($this->_checkPdoError($stm)) {
			return false;
		}

		if ($stm->rowCount()) {
			return true;
		}	
		return false;
	}


	public function addAuthor($author) {
		$this->_initDbConnection();
		
		$stm = $this->_prepareStatement('author', 'add');
		$stm->execute(array(
			':name'  => $author->name,
			':url'   => (!empty($author->url))?$author->url:'',
			':email' => (!empty($author->email))?$author->email:''
		));

		if ($this->_isPdoError($stm)) {
			return false;
		}		
		
		return true;
	}

	public function getAuthors() {
		$this->_initDbConnection();
		
		$stm = $this->_prepareStatement('author', 'getAll');		
		$stm->execute();
		
		if ($this->_checkPdoError($stm)) {
			return NULL;
		}

		$authors = array();
		while($row = $stm->fetchObject()) {
			$authors[] = $row;
		}
		return $authors;
	}

	public function getAuthorById($id) {
		$this->_initDbConnection();
		
		$stm = $this->_prepareStatement('author', 'getById');		
		$stm->execute(array(
			':id' => $id
		));
		
		if ($this->_checkPdoError($stm)) {
			return false;
		}

		if ($author = $stm->fetchObject()) {
			return $author;
		}	
		return NULL;
	}

	public function getAuthor($name) {
		$this->_initDbConnection();
		
		$stm = $this->_prepareStatement('author', 'getByName');		
		$stm->execute(array(
			':name' => $name
		));
		
		if ($this->_checkPdoError($stm)) {
			return false;
		}

		if ($author = $stm->fetchObject()) {
			return $author;
		}	
		return NULL;
	}

	####################################################################
	##
	## Private and protected methods
	##
	
	/**
		_initDbConnection: lazy initialisation of connection and database. 
			initialises connection in $this->conn, or exits. Checks that 
			all the database tables exists, creating them along the way.
	**/
	protected function _initDbConnection() {
		if (!empty($this->db)) {
			return;
		}
		
		// Create a new connection
		if (empty($this->config['datasource'])) {
			die("FeedAggregatorPdoStorage: no datasource configured\n");
		}

		// Initialise a new database connection and associated schema.
		$db = new PDO($this->config['datasource']); 
		$this->_initDbSchema();
		
		// Check the database tables exist
		$this->_initDbTables($db);

		// Database successful, make it ready to use
		$this->db = $db;
	} 

	/**
		_initDbTables: lazy creates missing db tables based on those
			specified in the schame
		
		@param $db - connection to a db
	**/
	protected function _initDbTables($db) {
		$tables = array_keys($this->schema);
		$buffer = array();
		foreach($this->schema as $sql) {
			$buffer[] = $sql['create'];
		}
		$db->exec(implode("\n", $buffer));

		// Check for fatal failures?
		if ($db->errorCode() !== '00000') {
			$info = $db->errorInfo();
			die('FeedAggregatorPdoStorage->_initDbTables: PDO Error: ' . 
				implode(', ', $info) . "\n");
		}
	}
	
	protected function _isPdoError($stm) {
		// Check for errors
		if ($stm->errorCode() !== '00000') {
			return true;
		}
		return false;
	}
	
	protected function _checkPdoError($stm) {
		// Check for fatal failures?
		if ($stm->errorCode() !== '00000') {
			$info = $stm->errorInfo();
			echo 'PDO Error: ' . implode(', ', $info) . "\n";
			return true;
		}
		return false;
	}
	
	protected function _prepareStatement($table, $queryKey) {
		$cacheKey = "{$table}:{$queryKey}";
		if (empty($this->stmCache[$cacheKey])) {
			$stm = $this->db->prepare($this->schema[$table][$queryKey]);	
			$this->stmCache[$cacheKey] = $stm;
		}
		return $this->stmCache[$cacheKey];
	}

	/**
		_initDbSchema: lazily initialises the $this->schema array 
			with the database schema currently in use. Values should 
			be using Prepared query syntax.
	**/
	protected function _initDbSchema() {
		if (!empty($this->schema)) {
			return;
		}
		
		#################################################################
		#
		# TABLE: feed
		#
		
		$this->schema['feed']['create'] = <<<SQL
CREATE TABLE IF NOT EXISTS `feed` (
	id          INTEGER PRIMARY KEY AUTOINCREMENT,
	url         VARCHAR(255) UNIQUE,
	title       VARCHAR(255),
	
	type        VARCHAR(1),
	status      VARCHAR(1),
	created     DATETIME,
	lastUpdated DATETIME,

	lastPolled  DATETIME,
	nextPoll    DATETIME
);
SQL;

		$this->schema['feed']['add'] = <<<SQL
INSERT INTO `feed` 
(id, url, title, type, status, created, lastUpdated, lastPolled, nextPoll)
VALUES
(NULL, :url, :title, :type, :status, 
 :created, :lastUpdated, :lastPolled, :nextPoll);
SQL;

		$this->schema['feed']['getAll'] = <<<SQL
SELECT * FROM `feed`;
SQL;

		$this->schema['feed']['getById'] = <<<SQL
SELECT * FROM `feed`
WHERE
	id = :id;
SQL;

		$this->schema['feed']['getByUrl'] = <<<SQL
SELECT * FROM `feed`
WHERE
	url = :url;
SQL;

		$this->schema['feed']['deleteById'] = <<<SQL
DELETE FROM `feed`
WHERE
	id = :id;
SQL;

		$this->schema['feed']['deleteByUrl'] = <<<SQL
DELETE FROM `feed`
WHERE
	url = :url;
SQL;

		#################################################################
		#
		# TABLE: author
		#

		$this->schema['author']['create'] = <<<SQL
CREATE TABLE IF NOT EXISTS `author` (
	id    INTEGER PRIMARY KEY AUTOINCREMENT,
	name  VARCHAR(255),
	url   VARCHAR(255),
	email VARCHAR(255),
	
	UNIQUE(name, url)
);
SQL;

		$this->schema['author']['insert'] = <<<SQL
INSERT INTO `author`
(id, name, url, email)
VALUES
(NULL, :name, :url, :email)
SQL;

		$this->schema['author']['getAll'] = <<<SQL
SELECT * FROM `author`;
SQL;

		$this->schema['author']['getByName'] = <<<SQL
SELECT * FROM `author`
WHERE
	name = :name;
SQL;

		$this->schema['author']['getById'] = <<<SQL
SELECT * FROM `author`
WHERE
	id = :id;
SQL;

		#################################################################
		#
		# TABLE: entry
		#
		
		$this->schema['entry']['create'] = <<<SQL
CREATE TABLE IF NOT EXISTS `entry` (
	id          INTEGER PRIMARY KEY AUTOINCREMENT,
	url         VARCHAR(255) UNIQUE,
	title       VARCHAR(255),
	atomid      VARCHAR(255),
	author_id   INTEGER,
	content     TEXT,
	created     DATETIME,
	updated     DATETIME

);
SQL;


		#################################################################
		#
		# TABLE: feedentry
		#
		
		$this->schema['feedentry']['create'] = <<<SQL
CREATE TABLE IF NOT EXISTS `feedentry` (
	feed_id  INTEGER,
	entry_id INTEGER,
	created  DATETIME,

	PRIMARY KEY(feed_id, entry_id)	
);
SQL;


	}

}

?>