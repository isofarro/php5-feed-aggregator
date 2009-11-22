PHP5 Feed Aggregator
====================

A simplified Feed aggregator that stores simplified Atom data structures to a PDO data source.


	$feedUrl = 'http://isolani.co.uk/articles.rdf';
    $aggregator = new FeedAggregator(array(
		// Where the data is stored. Any PDO data source
		'datasource' => 'sqlite:/tmp/db-aggregator.db'
	));
	
	// Adding a new feed to the aggregator
	$aggregator->addFeed($feedUrl);
	
	// Update the feeds - this should be running off a cron
	$aggregator->updateFeeds();
	
	// Requesting the most recent item in the feed
	$feedItems = $aggregator->getFeedItems($feedUrl, 1);
	print_r($feedItems);
	

The simplifed PHP data structure for an Atom entry looks like this:

    stdClass Object
        (
            [title] => Entry title
            [id] => tag:example.com:/unit/test/entry
            [url] => http://example.com/test-entry.html
            [author] => stdClass Object
                (
                    [name] => Entry Author
                )
            [published] => 2009-10-20T18:19:55+01:00
            [content] => Unit test entry content
         )

The idea is that a normalised php5 feed parser returns a simplified data structure for any feed item, and this simplified data structure is stored and returned by this aggregator.


