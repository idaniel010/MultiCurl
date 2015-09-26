<?php

include_once 'MultiCurl.class.php';

/////////////////////////////////////////////
//		Example 3, set custom curl settings
/////////////////////////////////////////////

// create the MultiCurl object using default settings (pool size = 5)
$example3 = new MultiCurl();

// curl settings for each request
$customCurlSettings = array(
        CURLOPT_FOLLOWLOCATION => false // do not follow redirects inside of the curl request
    );

// set the custom curl settings
$example3->setCurlSettings($customCurlSettings);

// define the requests
$requests = array(
        array(
            'url' => 'http://www.google.com/search?q=123',
        ),
        array(
            'url' => 'http://www.google.com/search?q=321',
            'curl_settings' => array(
                CURLOPT_FOLLOWLOCATION => true // this will overwrite the global curl settings for this curl request
            )
        )
    );

// execute the requests
$results = $example3->processRequests($requests);
echo '<h2> Results: </h2>';
echo 'Without CURLOPT_FOLLOWLOCATION: ' . $results[0]['url'] . ': ' . $results[0]['status'] . ', content length ' . strlen($results[0]['response']) . ')' . $results[0]['response'] . '<br/><br/><hr/>';
echo 'With CURLOPT_FOLLOWLOCATION: ' . $results[1]['url'] . ': ' . $results[1]['status'] . ', content length ' . strlen($results[1]['response']) . ')' . $results[1]['response'] . '<br/><br/><hr/>';


?>
