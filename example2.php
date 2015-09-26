<?php

include_once 'MultiCurl.class.php';

/////////////////////////////////////////////
//		Example 2, send get & post params
/////////////////////////////////////////////


// get a list of requests
$requests = array(
	    array(
	    	'url' => 'https://www.google.com/search?q=100',
			'get' => array('tbm' => 'isch') // used to show images search in google
		),
		array(
	    	'url' => 'https://www.google.com/search?q=200',
			'post' => array('test' => 'true') // just sending some post data to google :D
		),
		array(
	    	'url' => 'https://www.google.com/search?q=300',
	    	'get' => array('tbm' => 'isch'), // used to show images search in google
			'post' => array('test' => 'true') // just sending some post data to google :D
		)
	);

// create the MultiCurl object using default settings (pool size = 5)
$example2 = new MultiCurl();

$results = $example2->processRequests($requests);

// show the results

echo $results[0]['url'] . ': ' . $results[0]['status'] . ', content length ' . strlen($results[0]['response']) . ')<br/><br/><hr/>';
echo $results[1]['url'] . ': ' . $results[1]['status'] . ', content length ' . strlen($results[1]['response']) . ')' . $results[1]['response'] . '<br/><br/><hr/>';
echo $results[2]['url'] . ': ' . $results[2]['status'] . ', content length ' . strlen($results[2]['response']) . ')' . $results[2]['response'] . '<br/><br/><hr/>';

?>
