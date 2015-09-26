<?php

include_once 'MultiCurl.class.php';

/////////////////////////////////////////////
//		Example 1, run some requests
/////////////////////////////////////////////

// run 10 curls in parallel
$poolSize = 10; 

// set a list of requests
$requests = array(
    array('url' => 'https://www.google.com/search?q=0'),
    array('url' => 'https://www.google.com/search?q=1'),
    array('url' => 'https://www.google.com/search?q=2'),
    array('url' => 'https://www.google.com/search?q=3'),
    array('url' => 'https://www.google.com/search?q=4'),
    array('url' => 'https://www.google.com/search?q=5'),
    array('url' => 'https://www.google.com/search?q=6'),
    array('url' => 'https://www.google.com/search?q=7'),
    array('url' => 'https://www.google.com/search?q=8'),
    array('url' => 'https://www.google.com/search?q=9'),
    array('url' => 'https://www.google.com/search?q=10'),
    array('url' => 'https://www.google.com/search?q=11'),
    array('url' => 'https://www.google.com/search?q=12'),
    array('url' => 'https://www.google.com/search?q=13'),
    array('url' => 'https://www.google.com/search?q=14'),
    array('url' => 'https://www.google.com/search?q=15'),
    array('url' => 'https://www.google.com/search?q=16'),
    array('url' => 'https://www.google.com/search?q=17'),
    array('url' => 'https://www.google.com/search?q=18'),
    array('url' => 'https://www.google.com/search?q=19'),
    array('url' => 'https://www.google.com/search?q=20'),
    array('url' => 'https://www.google.com/search?q=21'),
    array('url' => 'https://www.google.com/search?q=22'),
    array('url' => 'https://www.google.com/search?q=23'),
    array('url' => 'https://www.google.com/search?q=24')
);

// start debugging running time
$startTime = round(microtime(true) * 1000);

// create the MultiCurl object
$example1 = new MultiCurl($poolSize);

// execute the curls in parallel
$results = $example1->processRequests($requests);

// end debugging the running time
$endTime = round(microtime(true) * 1000);

// show the results
echo (count($results) . ' requests were done in ' . ($endTime - $startTime) . ' ms <br/>');
echo 'results: <br/>';
foreach ($results as $key => $value) {
    echo $value['url'] . ': ' . $value['status'] . ', content length ' . strlen($value['response']) . ')<br/>';
}

unset($poolSize, $example1, $requests, $results);

?>
