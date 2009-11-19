<?php

class FeedAggregatorPdoStorage {
	// Database connection
	var $db;

	// Database schema
	var $schema;
	
	// Prepared statement cache
	var $stmCache = array();
	
	// Configuration
	var $confi = array(
		'datasource' => 'sqlite:/tmp/tmp-feed-aggregator.db';
	);
	
	
	/**
		__construct: initialising storage
		@param config array, an optional configuration hash
	**/
	public __construct($config=false) {
		if ($config) {
			$this->setConfig($config);
		}
	}
	
	public function setConfig($config) {
		if (is_array($config)) {
			$this->config = array_merge($this->config, $config);
		}
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
			die('TwitterForgePdoStorage->_initDbTables: PDO Error: ' . 
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