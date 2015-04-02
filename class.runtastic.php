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

	class Runtastic {
		private $loginUsername;
		private $loginPassword;
		private $loginUrl;
		private $logoutUrl;
		private $sessionsApiUrl;
		private $ch;
		private $cookieJar;
		private $loggedIn;
		private $runtasticUsername;
		private $runtasticUid;
		private $runtasticToken;
		private $runtasticRawData;
		private $doc;
		private $timeout;
		
		public function __construct() {
			libxml_use_internal_errors(true);
			$this->loginUrl = "https://www.runtastic.com/en/d/users/sign_in.json";
			$this->logoutUrl = "https://www.runtastic.com/en/d/users/sign_out";
			$this->sessionsApiUrl = "https://www.runtastic.com/api/run_sessions/json";
			$this->ch = null;
			$this->doc = new DOMDocument();
			$this->cookieJar = getcwd() . "/cookiejar";
			$this->runtasticToken = "";
			$this->loggedIn = false;
			$this->timeout = 10;
		}
		
		public function login() {
            if ($this->ch == null) {
                $this->ch = curl_init();
            }

			$postData = array(
				"user[email]" => $this->loginUsername,
				"user[password]" => $this->loginPassword,
				"authenticity_token" => $this->runtasticToken,
			);

			curl_setopt($this->ch, CURLOPT_URL, $this->loginUrl); 
			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($this->ch,CURLOPT_POST, count($postData));
			curl_setopt($this->ch,CURLOPT_POSTFIELDS, $postData);
			curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->cookieJar);
			curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->cookieJar);

			$responseOutput = curl_exec($this->ch); 
			$responseStatus = curl_getinfo($this->ch);
			
			$responseOutputJson = json_decode($responseOutput);

			if ($responseStatus["http_code"] == 200) {
				$this->doc->loadHTML($responseOutputJson->update);
				$inputTags = $this->doc->getElementsByTagName('input');
				foreach ($inputTags as $inputTag) {
					if ($inputTag->getAttribute("name") == "authenticity_token") {
						$this->runtasticToken = $inputTag->getAttribute("value");
					}
				}
			
				$aTags = $this->doc->getElementsByTagName('a');
				foreach ($aTags as $aTag) {
					if (preg_match("/\/en\/users\/(.*)\/dashboard/", $aTag->getAttribute("href"), $matches)) {
						$this->runtasticUsername = $matches[1];
					}
				}
				
				$sessionsUrl = "https://www.runtastic.com/en/users/" . $this->runtasticUsername . "/sport-sessions";
			
				curl_setopt($this->ch, CURLOPT_URL, $sessionsUrl);
				curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1); 
				curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->cookieJar);
				curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->cookieJar);
				curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
				curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
				
				$frontpageOutput = curl_exec($this->ch); 
								
				$this->doc->loadHTML($frontpageOutput);
				$scriptTags = $this->doc->getElementsByTagName('script');
				foreach ($scriptTags as $scriptTag) {
					if(strstr($scriptTag->nodeValue, 'index_data')) {
						$this->runtasticRawData = $scriptTag->nodeValue;
					}
				}

				preg_match("/uid: (.*)\,/", $this->runtasticRawData, $matches);
				$this->runtasticUid = $matches[1];
			
				$this->loggedIn = true;
				return true;
			} else {
				$this->loggedIn = false;
				return false;
			}
		}
		
		public function logout() {
			curl_setopt($this->ch, CURLOPT_URL, $this->logoutUrl);
			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1); 
			curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->cookieJar);
			curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->cookieJar);
			curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
			curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
			if (curl_exec($this->ch)) {
				curl_close($this->ch);
                $this->ch = null;
                $this->loggedIn = false;
				return true;
			} else {
				return false;
			}
		}
		
		public function setUsername($loginUsername) {
			$this->loginUsername = $loginUsername;
		}
		
		public function setPassword($loginPassword) {
			$this->loginPassword = $loginPassword;
		}
		
		public function setTimeout($timeout) {
			$this->timeout = $timeout;
		}
		
		public function getUid() {
			if ($this->loggedIn) {
				return $this->runtasticUid;
			} else {
				return false;
			}
		}
		
		public function getUsername() {
			if ($this->loggedIn) {
				return $this->runtasticUsername;
			} else {
				return false;
			}
		}
		
		public function getToken() {
			if ($this->loggedIn) {
				return $this->runtasticToken;
			} else {
				return false;
			}
		}
		
        /**
         * Returns all activities.
         * If
         *  - $iWeek is set, only the requested week will be returned.
         *  - $iMonth is set, only the requested month will be returned.
         *  - $iYear is set, only the requested year will be returned.
         *
         * $iWeek and $iMonth can be used together with $iYear. if $iYear is null, the current year will
         * be used for filtering.
         *
         * @param int|null $iWeek Number of the wanted week.
         * @param int|null $iMonth Number of the requested month.
         * @param int|null $iYear Number of the requested year.
         * @return bool|mixed
         */
        public function getActivities($iWeek = null, $iMonth = null, $iYear = null) {
            if (!$this->loggedIn) $this->login();
            if ($this->loggedIn) {

                preg_match("/var index_data = (.*)\;/", $this->runtasticRawData, $matches);
                $itemJsonData = json_decode($matches[1]);
                $items = array();

                if ($iMonth != null) {
                    if ($iMonth < 10) {
                        $iMonth = "0" . (int)$iMonth;
                    }
                }

                foreach ($itemJsonData as $item) {
                    if ($iWeek != null) { /* Get week statistics */
                        if ($iYear == null) {
                            $iYear = date("Y");
                        }
                        $sMonday = date("Y-m-d", strtotime("{$iYear}-W{$iWeek}"));
                        $sSunday = date("Y-m-d", strtotime("{$iYear}-W{$iWeek}-7"));
                        if ($sMonday <= $item[1] && $sSunday >= $item[1])
                            $items[] = $item[0];

                    } elseif ($iMonth != null) { /* Get month statistics */
                        if ($iYear == null) {
                            $iYear = date("Y");
                        }
                        $tmpDate = $iYear . "-" . $iMonth . "-";
                        if ($tmpDate . "01" <= $item[1] && $tmpDate . "31" >= $item[1])
                            $items[] = $item[0];
                    } elseif ($iYear != null) { /* Get year statistics */
                        $tmpDate = $iYear . "-";
                        if ($tmpDate . "01-01" <= $item[1] && $tmpDate . "12-31" >= $item[1])
                            $items[] = $item[0];
                    } else { /* Get all statistics */
                        $items[] = $item[0];
                    }
                }
				
				/* Since we can only get the latest 420 activities, we sort them by ID to get the latest ones */
				arsort($items);
				$items = array_splice($items, 0, 419);

                $itemList = implode($items, ",");
				
                $postData = array(
                    "user_id" => $this->getUid(),
                    "items" =>  $itemList, 
                    "authenticity_token" => $this->getToken()
                );
				
                curl_setopt($this->ch, CURLOPT_URL, $this->sessionsApiUrl);
                curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
                curl_setopt($this->ch, CURLOPT_POST, count($postData));
                curl_setopt($this->ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($this->ch, CURLOPT_COOKIEFILE, $this->cookieJar);
                curl_setopt($this->ch, CURLOPT_COOKIEJAR, $this->cookieJar);
                curl_setopt($this->ch, CURLOPT_TIMEOUT, $this->timeout);
				
                $sessionsOutput = curl_exec($this->ch);
                $this->logout();

                return new RuntasticActivityList($sessionsOutput);
            } else {
                return false;
            }
        }
    }

    class RuntasticActivityList implements ArrayAccess {
        public function __construct($sJSON = false) {
            if ($sJSON) $this->_set(json_decode($sJSON));
        }

        public function filterBy($aFilter) {
            $tmp = array();
            foreach ($this as $oActivity) {
                $blKeep = false;
                foreach ($aFilter as $key => $val) {
                    if ($oActivity->$key == $val) {
                        $blKeep = true;
                    } else {
                        $blKeep = false;
                        break;
                    }
                }
                if ($blKeep)
                    $tmp[] = $oActivity;
            }
            $this->_set($tmp, true);
        }

        private function _set($data, $blClean = false) {
            if ($blClean) $this->_reset();

            foreach ($data AS $key => $value) {
                $this->$key = $value;
            }
            return $this;
        }

        private function _reset() {
            foreach ($this as $key => $val) {
                unset($this->$key);
            }
        }

        // ArrayAccess functions //
        public function offsetExists($offset) {
            if (isset($this->$offset)) return true;
            return false;
        }

        public function offsetGet($offset) {
            if (isset($this->$offset)) return $this->$offset;
            return false;
        }

        public function offsetSet($offset, $value) {
            if (is_null($offset)) {
                $this->_set($value);
            } else {
                $this->_set(array($offset => $value));
            }
        }

        public function offsetUnset($offset) {
            unset($this->$offset);
        }
    }
?>
