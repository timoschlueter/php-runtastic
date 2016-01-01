<?php

/*

The MIT License (MIT)

Copyright (c) 2014 Timo Schlueter <timo.schlueter@me.com>

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

*/

namespace Runtastic;

class Runtastic
{
    /**
     * HTTP Responses
     */
    const HTTP_OK   = 200;

    /**
     * Runtastic API Urls
     */
    const RUNTASTIC_LOGIN_URL           = "https://www.runtastic.com/en/d/users/sign_in.json";
    const RUNTASTIC_LOGOUT_URL          = "https://www.runtastic.com/en/d/users/sign_out";
    const RUNTASTIC_SESSIONS_URL        = "https://www.runtastic.com/api/run_sessions/json";
    const RUNTASTIC_SPORT_SESSIONS_URL  = "https://www.runtastic.com/en/users/%s/sport-sessions";

    /**
     * Runtastic Credentials
     */
    private $loginUsername;
    private $loginPassword;

    /**
     * Request Trace
     */
    private $lastRequest;
    private $lastRequestData;
    private $lastRequestInfo;

    /**
     * Runtastic User Data after login
     */
    private $username;
    private $uid;
    private $token;
    private $rawData;

    /**
     * Other private variables
     */
    private $doc;
    private $loggedIn  = false;
    private $timeout   = 10;
    private $cookieJar = "/tmp/cookiejar";

    /**
     * Runtastic constructor.
     */
    public function __construct()
    {
        libxml_use_internal_errors(true);
        $this->doc = new \DOMDocument();
    }

    /**
     * Set Login Username.
     *
     * @param  string $loginUsername
     * @return Runtastic
     */
    public function setUsername($loginUsername)
    {
        $this->loginUsername = $loginUsername;

        return $this;
    }

    /**
     * Set Login Password.
     *
     * @param  string $loginPassword
     * @return Runtastic
     */
    public function setPassword($loginPassword)
    {
        $this->loginPassword = $loginPassword;

        return $this;
    }

    /**
     * Set Timeout.
     *
     * @param  int $timeout
     * @return Runtastic
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Set CookieJar File
     *
     * @param  string $cookieJar
     * @return Runtastic
     */
    public function setCookieJar($cookieJar)
    {
        $this->cookieJar = $cookieJar;

        return $this;
    }

    /**
     * Get Username.
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Get Uid.
     *
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Get Token.
     *
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Get Raw Data.
     *
     * @return array
     */
    public function getRawData()
    {
        return $this->rawData;
    }

    /**
     * Get Response Status Code.
     *
     * @return int|null
     */
    public function getResponseStatusCode()
    {
        if (isset($this->lastRequestInfo['http_code'])) {
            return $this->lastRequestInfo['http_code'];
        }

        return null;
    }

    /**
     * Set Data From Response.
     *
     * This function parse the given request and save some variables
     * (such as token, username, ...) into the class for future needs.
     *
     * @param  string $response
     * @return void
     */
    private function setDataFromResponse($response)
    {
        $this->doc->loadHTML($response);

        $inputTags = $this->doc->getElementsByTagName('input');
        foreach ($inputTags as $inputTag) {
            if ($inputTag->getAttribute("name") == "authenticity_token") {
                $this->token = $inputTag->getAttribute("value");
                break;
            }
        }

        $aTags = $this->doc->getElementsByTagName('a');
        foreach ($aTags as $aTag) {
            if (preg_match("/\/en\/users\/(.*)\/dashboard/", $aTag->getAttribute("href"), $matches)) {
                $this->username = $matches[1];
                break;
            }
        }

        $scriptTags = $this->doc->getElementsByTagName('script');
        foreach ($scriptTags as $scriptTag) {
            if (strstr($scriptTag->nodeValue, 'index_data')) {
                $this->rawData = $scriptTag->nodeValue;
                break;
            }
        }

        preg_match("/uid: (.*)\,/", $this->rawData, $matches);
        if (isset($matches[1])) {
            $this->uid = $matches[1];
        }
    }

    /**
     * Login User to Runtastic
     *
     * @return bool
     */
    public function login()
    {
        $this->loggedIn = false;

        $postData = [
            "user[email]"           => $this->loginUsername,
            "user[password]"        => $this->loginPassword,
            "authenticity_token"    => $this->token,
        ];

        $responseOutputJson = $this->post(self::RUNTASTIC_LOGIN_URL, $postData);

        if ($this->getResponseStatusCode() == self::HTTP_OK) {
            $this->setDataFromResponse($responseOutputJson->update);

            $frontpageOutput = $this->get(sprintf(self::RUNTASTIC_SPORT_SESSIONS_URL, $this->getUsername()), [], false);
            $this->setDataFromResponse($frontpageOutput);

            $this->loggedIn = true;
        }

        return $this->loggedIn;
    }

    /**
     * Logout User's Session
     *
     * @return void
     */
    public function logout()
    {
        $this->get(self::RUNTASTIC_LOGOUT_URL);

        if ($this->getResponseStatusCode() == self::HTTP_OK) {
            $this->loggedIn = false;
        }
    }

    /**
     * Returns all activities.
     *
     * If
     *  - $iWeek is set, only the requested week will be returned.
     *  - $iMonth is set, only the requested month will be returned.
     *  - $iYear is set, only the requested year will be returned.
     *
     * $iWeek and $iMonth can be used together with $iYear. if $iYear is null, the current year will
     * be used for filtering.
     *
     * @param  int|null $iWeek  Number of the wanted week.
     * @param  int|null $iMonth Number of the requested month.
     * @param  int|null $iYear  Number of the requested year.
     * @return bool|mixed
     */
    public function getActivities($iWeek = null, $iMonth = null, $iYear = null)
    {
        $response = [];

        if (!$this->loggedIn) {
            $this->login();
        }

        if ($this->loggedIn) {
            preg_match("/var index_data = (.*)\;/", $this->rawData, $matches);
            $itemJsonData = json_decode($matches[1]);
            $items = [];

            // Complete $iMonth with leading zeros
            if (!is_null($iMonth)) {
                $iMonth = str_pad($iMonth, 2, '0', STR_PAD_LEFT);
            }

            foreach ($itemJsonData as $item) {
                if ($iWeek != null) { /* Get week statistics */
                    if ($iYear == null) {
                        $iYear = date("Y");
                    }
                    $sMonday = date("Y-m-d", strtotime("{$iYear}-W{$iWeek}"));
                    $sSunday = date("Y-m-d", strtotime("{$iYear}-W{$iWeek}-7"));
                    if ($sMonday <= $item[1] && $sSunday >= $item[1]) {
                        $items[] = $item[0];
                    }
                } elseif ($iMonth != null) { /* Get month statistics */
                    if ($iYear == null) {
                        $iYear = date("Y");
                    }
                    $tmpDate = $iYear."-".$iMonth."-";
                    if ($tmpDate."01" <= $item[1] && $tmpDate."31" >= $item[1]) {
                        $items[] = $item[0];
                    }
                } elseif ($iYear != null) { /* Get year statistics */
                    $tmpDate = $iYear."-";
                    if ($tmpDate."01-01" <= $item[1] && $tmpDate."12-31" >= $item[1]) {
                        $items[] = $item[0];
                    }
                } else { /* Get all statistics */
                    $items[] = $item[0];
                }
            }

            // Sort activities by ID (which is the same that sorting by date)
            arsort($items);

            $postData = [
                "user_id"            => $this->getUid(),
                "items"              => join(',', $items),
                "authenticity_token" => $this->getToken(),
            ];

            $response = $this->post(self::RUNTASTIC_SESSIONS_URL, $postData);
        }

        return new RuntasticActivityList($response);
    }

    /**
     * Appends query array onto URL
     *
     * @param  string $url
     * @param  array  $query
     * @return string
     */
    protected function parseGet($url, $query)
    {
        if (!empty($query)) {
            $append = strpos($url, '?') === false ? '?' : '&';

            return $url.$append.http_build_query($query);
        }

        return $url;
    }

    /**
     * Parses JSON as PHP object
     *
     * @param  string $response
     * @return object
     */
    protected function parseResponse($response)
    {
        return json_decode($response);
    }

    /**
     * Makes HTTP Request to the API
     *
     * @param  string      $url
     * @param  array       $parameters
     * @param  string|null $request
     * @param  bool        $json
     * @return object|null
     */
    protected function request($url, $parameters = [], $request = null, $json = true)
    {
        $this->lastRequest     = $url;
        $this->lastRequestData = $parameters;

        $curl = curl_init($url);

        $curlOptions = array(
            CURLOPT_URL             => $url,
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_COOKIEFILE      => $this->cookieJar,
            CURLOPT_COOKIEJAR       => $this->cookieJar,
            CURLOPT_TIMEOUT         => $this->timeout,
        );

        if (!empty($parameters) || !empty($request)) {
            if (!empty($request)) {
                $curlOptions[CURLOPT_CUSTOMREQUEST] = $request;
                $parameters = http_build_query($parameters);
            } else {
                $curlOptions[CURLOPT_POST] = true;
            }

            $curlOptions[CURLOPT_POSTFIELDS] = $parameters;
        }

        curl_setopt_array($curl, $curlOptions);
        $response = curl_exec($curl);
        $this->lastRequestInfo = curl_getinfo($curl);
        curl_close($curl);

        return !$response ? null : ($json ? $this->parseResponse($response) : $response);
    }

    /**
     * Sends GET request to specified API endpoint
     *
     * @param  string $request
     * @param  array  $parameters
     * @param  bool   $json
     * @return string
     */
    public function get($request, $parameters = [], $json = true)
    {
        $requestUrl = $this->parseGet($request, $parameters);

        return $this->request($requestUrl, [], null, $json);
    }

    /**
     * Sends PUT request to specified API endpoint
     *
     * @param  string $request
     * @param  array  $parameters
     * @param  bool   $json
     * @return string
     */
    public function put($request, $parameters = [], $json = true)
    {
        return $this->request($request, $parameters, 'PUT', $json);
    }

    /**
     * Sends POST request to specified API endpoint
     *
     * @param  string $request
     * @param  array  $parameters
     * @param  bool   $json
     * @return string
     */
    public function post($request, $parameters = [], $json = true)
    {
        return $this->request($request, $parameters, null, $json);
    }

    /**
     * Sends DELETE request to specified API endpoint
     *
     * @param  string $request
     * @param  array  $parameters
     * @param  bool   $json
     * @return string
     */
    public function delete($request, $parameters = [], $json = true)
    {
        return $this->request($request, $parameters, 'DELETE', $json);
    }
}
