<?php

/**
 * This class runs concurent curl connection according to a pool size.
 * It takes an array describing all the curl requests and curl settings and runs the requests in parallel according to the pool size.
 *
 * @author Daniel Ionita
 * @version 0.1 simply run concurent curl connection according to a pool size and custom curl settings set for individual requests or for all requests.
 */

class MultiCurl
{
	/**
	 * The number of concurent connections.
	 * @var integer
	 */
	protected $connectionsPoolSize = 5;

    /**
     * The curl settings to use for all the requests in array format.
     * @var array
     */
    protected $customCurlSettings = array();

    /**
     * The curl settings used by default for requests.
     * @var array
     */
    protected $defaultCurlSettings = array(
        CURLOPT_RETURNTRANSFER  => 1,       // needed to get the response
        CURLOPT_SSL_VERIFYPEER  => false,   // do not verify ssl peer, used to skip some frequent errors, but makes the requests more vulnerable to MIT attack
        CURLOPT_CONNECTTIMEOUT  => 60,      // connection timeout in seconds
        CURLOPT_TIMEOUT         => 60,      // response timeout in seconds
        CURLOPT_FOLLOWLOCATION  => true,    // follow redirections
    );

    /**
     * The constructor can set  $connectionsPoolSize and $customCurlSettings.
     *
     * @param integer $connectionsPoolSize
     * @param array   $customCurlSettings
     *
     * @return void
     */
    function __construct($connectionsPoolSize = 5, $customCurlSettings = array()) {
        // you can later set Pool Size and Custom Curl Settings
        $this->setPoolSize($connectionsPoolSize);
        $this->setCurlSettings($customCurlSettings);
    }

	/**
	 * Set the number of concurent connections.
	 *
	 * @param integer $connectionsPoolSize
     *
	 * @return false if fails or true when success
	 */
	public function setPoolSize($connectionsPoolSize)
	{
		if (empty($connectionsPoolSize) || !is_numeric($connectionsPoolSize) || $connectionsPoolSize < 0) {
            return false;
        }

        // set and filter data
		$this->connectionsPoolSize = intval($connectionsPoolSize);

		return true;
	}

    /**
     * Set the custom Curl Parameters.
     *
     * @param array $customCurlSettings
     *
     * @return false if fails or true when success
     */
    public function setCurlSettings($customCurlSettings)
    {
        if (empty($customCurlSettings) || !is_array($customCurlSettings)) {
            return false;
        }

        // set data
        $this->customCurlSettings = $customCurlSettings;

        return true;
    }

	/**
     * Prepares and make multicurls based on request
     *
     * @param array $request - list of sub-arrays: array('url', 'get' or 'post', 'curl_settings')
     *
     * @return $response - array is the same list given as input, but with 'response' and 'status' fields added to each sub-array
     */
    public function processRequests($requests = array())
    {
    	if (empty($requests) || !is_array($requests)) {
            return false;
        }

        if (empty($this->connectionsPoolSize) || $this->connectionsPoolSize < 0) {
        	return false;
        }

        // number of requests
        $requestCount = count($requests);

        // keep track of current running connections
        $runningConns = array();

        // init multicurl handler
        $multiCurlHandler = curl_multi_init();

        // make sure the concurent connection fit the requests count
        if ($this->connectionsPoolSize > $requestCount) {
        	$this->connectionsPoolSize = $requestCount;
        }

        // set the array pointer to the first position
        reset($requests);

        // add curls to the start line
        for ($i = 0 ; $i < $this->connectionsPoolSize ; $i++) {
            // get the current element of the array and set the pointer to the next one
            $currentElement = each($requests);
            $currentConnKey = $currentElement['key'];

        	// accept proper requests only
        	if (!empty($requests[$currentConnKey]) && is_array($requests[$currentConnKey])) {
        		// add curl connection to stack and check for failure
        		$curlObject = $this->addCurlHandle($multiCurlHandler, $requests[$currentConnKey]);

	        	if ($curlObject === false) {
                    // set status
	        		$requests[$currentConnKey]['status'] = 'failed';
	        	} else {
                    // set status
	        		$requests[$currentConnKey]['status'] = 'processing';

	        		// needed when the task is finished
	        		$requests[$currentConnKey]['curl_handler'] = $curlObject;

                    // track the current running connections
                    $runningConns[] = $currentConnKey;
	        	}
	        }
        }

        // keep track for the next curl connection to use when a new connection is released
        $nextCurlConnIndex = $this->connectionsPoolSize;

        // start execute the curls and also add more connections when a connection is finished
        do {
            // execute requests
            curl_multi_exec($multiCurlHandler, $active);

            // wait for response
            if (curl_multi_select($multiCurlHandler) == 0) {
                // for safety, break when curl lib doesn't see any thread in progress
                break;
            }

    		// check if any curl handle finished
			$info = curl_multi_info_read($multiCurlHandler);
	        if ($info === false) {
	            continue;
	        }

	        // check the handle response
            if (isset($info['result']) && ($info['result'] == CURLE_OK)  && !empty($info['handle'])) {

                // variable used to store the request index position
                $resultIndex = -1;

                // identify the handler in the running list
                foreach ($runningConns as $key => $connIndex) {
                    if ($requests[$connIndex]['curl_handler'] === $info['handle']) {
                        // found it
                        $resultIndex = $connIndex;

                        // remove the handler index from the running list
                        unset($runningConns[$key]);
                        break;
                    }
                }

                // we have found the handler in our list, so store the response and remove the curl handler
                if ($resultIndex !== -1) {
                    // get the handle content
                    $content = curl_multi_getcontent($info['handle']);

                    // check the response
                    if ($content === FALSE) {
                        // set failed status
                        $requests[$resultIndex]['status'] = 'failed';

                        // record the curl error message
                        $requests[$resultIndex]['response'] = curl_errno($info['handle']) . ' - ' . curl_error($info['handle']);
                    } else {
                        // set success status
                        $requests[$resultIndex]['status'] = 'success';

                        // store the response
                        $requests[$resultIndex]['response'] = $content;
                    }

                    // close the curl handler
                    curl_multi_remove_handle($multiCurlHandler, $info['handle']);
                    curl_close($info['handle']);

                    // release memory
                    unset($info);
                    unset($requests[$resultIndex]['curl_handler']);
                }

                // get the current element of the array and set the pointer to the next elem
                $currentElement = each($requests);

                // if there are more tasks to do
                if (!empty($currentElement)) {
                    // get the current element key
                    $currentConnKey = $currentElement['key'];

                    // add curl connection to pool and check for failure
                    $curlObject = $this->addCurlHandle($multiCurlHandler, $requests[$currentConnKey]);

                    // check the curl object
                    if ($curlObject === false) {
                        // set failed status
                        $requests[$currentConnKey]['status'] = 'failed';
                    } else {
                        // set success status
                        $requests[$currentConnKey]['status'] = 'processing';

                        // needed when the task is finished
                        $requests[$currentConnKey]['curl_handler'] = $curlObject;

                        // track the current running connections
                        $runningConns[] = $currentConnKey;
                    }
                }
            }
		} while (count($runningConns) > 0); // there are still curl handlers to process

        return $requests;
    }

    /**
     * This function adds the the multicurl pool a new request.
     *
     * @param multi curl object &$multiCurlHandler
     * @param array $requestData    the array containing 'url', and optional: 'get', 'post', 'curl_settings'
     *
     * @return curl object created that was added to the multi curl object or false on failure
     */
    protected function addCurlHandle(&$multiCurlHandler, $requestData)
    {
        // dynamic connection name
        $connectionHandle = curl_init();

        if (empty($requestData) || empty($requestData['url'])) {
        	return false;
        }

        // prepare the URL and add the get parameters
        $url = $requestData['url'];
        if (!empty($requestData['get']) && is_array($requestData['get'])) {
            $queryStringStart = '?';

            // check if the url already has some query string
            if (strpos($requestData['url'], '?') !== false) {
                $queryStringStart = '&';
            }

            // concatenate the url for get request
	        $url .= $queryStringStart . http_build_query($requestData['get']);
	    }

	    // prepare the curl object basic settings
        curl_setopt($connectionHandle, CURLOPT_URL, $url);

	    // add the post data
       	if (!empty($requestData['post'])) {
	        curl_setopt($connectionHandle, CURLOPT_POST, 1);
	        curl_setopt($connectionHandle, CURLOPT_POSTFIELDS, $requestData['post']);
	    }

	    // add inline custom curl settings
        $inlineCustomCurlSettings = array();
	    if (!empty($requestData['curl_settings']) && is_array($requestData['curl_settings'])) {
	    	$inlineCustomCurlSettings = $requestData['curl_settings'];
	    }

        // set curl settings using priorities
        // inline curl settings overwrites custom settings and default settings
        // custom curl settings overwrites default settigs
        curl_setopt_array($connectionHandle, $inlineCustomCurlSettings + $this->customCurlSettings + $this->defaultCurlSettings);

	    // check for failure
        if (curl_multi_add_handle($multiCurlHandler, $connectionHandle) !== 0) {
            return false;
        }

        // success
        return $connectionHandle;
    }
}

?>
