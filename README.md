#Runtastic PHP
Runtastic PHP is a class to gain easy access to Runtastic ([www.runtastic.com](http://www.runtastic.com)) activity data via PHP.
This is a very dirty approach since Runtastic doesn't offer an official API.

##REQUIREMENTS
This class requieres at least PHP 5.2 and the JSON and CURL (with SSL) extension.

##INSTALLATION
Simply include the class and you are good to go.

##CLASSES
###Runtastic
------
#### setUsername()

**[Mandatory]** Sets the username used for logging into Runtastic

#### setPassword()

**[Mandatory]** Sets the password used for logging into Runtastic

#### setTimeout()

Sets the connection timeout (in seconds) for cURL

#### login()

Logs into Runtastic (requires valid username and password)

#### logout()

Logs out and closes the cURL connection

#### getUsername()

Returns the Username used by Runtastic

#### getUid()

Returns the UID used by Runtastic

#### getToken()

Returns the session token

#### getActivities($week=null, $month=null, $year=null)
Returns every activity in your Runtastic account as a RuntasticActivityList (usable as array) of objects.
If
  - `$iWeek` is set, all activities within requested week will be returned (week starts on monday).
  - `$iMonth` is set, all activities within requested month will be returned.
  - `$iYear` is set, all activities within the requested year will be returned.

`$iWeek` and `$iMonth` can be used together with `$iYear`. if `$iYear` is null, the current year will be used for filtering.



### RuntasticActivityList
------
#### filterBy(array())
`$runtastic->getActivities()` returns an RuntasticActivityList.
Furthermore, you are able to filter the results (i.e. just cycling). For an example see below:


##EXAMPLE
This is an example which logs into runtastic, fetches every activity in your account and outputs internal Runtastic data (Username, UID) and a simple string.

```php
	<?php
		include("class.runtastic.php");
	
		$runtastic = New Runtastic();
		$runtastic->setUsername("user@example.com");
		$runtastic->setPassword("verysecurepassword");
		$runtastic->setTimeout(20);

		if ($runtastic->login()) {
			echo "Username: " . $runtastic->getUsername();
			echo "<br />";
			echo "UID: " . $runtastic->getUid();
			echo "<br />";
			echo "<br />";

		    // get all activities
			$myRuntasticActivities = $runtastic->getActivities();
			echo "My latest <b>" . $myRuntasticActivities[0]->type . "</b> activity was <b>" . $myRuntasticActivities[0]->feeling . "</b>!";

			// get current weeks activities
			$myRuntasticActivities = $runtastic->getActivities(date("W"));

            // get current month activities
            $myRuntasticActivities = $runtastic->getActivities(null, date("m"));

            // get all activites from week 1 of 2014
            $myRuntasticActivities = $runtastic->getActivities(1, null, 2014);

            // get all activities from 2013:
            $myRuntasticActivities = $runtastic->getActivities(null, null, 2013);

            // get all activities and filter by "type" = "strolling" and "weather" = "good":
            $myRuntasticActivities = $runtastic->getActivities();
            $myRuntasticActivities->filterBy(array("type"=>"strolling", "weather"=>"good"));

		}
	?>
```

#### Output:

	Username: Example-User
	UID: 1337

	My latest cycling activity was awesome!
	
ACTIVITY OBJECT
--------

This is what a typical activity object looks like:

	array(1) {
		[0]=>
			object(stdClass)#11 (24) {
			["id"]=>
			int(1111111)
			["type"]=>
			string(7) "cycling"
			["type_id"]=>
			int(3)
			["duration"]=>
			int(630003)
			["distance"]=>
			int(2435)
			["pace"]=>
			float(4.31213333333)
			["speed"]=>
			string(6) "13.914"
			["kcal"]=>
			int(48)
			["heartrate_avg"]=>
			int(0)
			["heartrate_max"]=>
			int(0)
			["elevation_gain"]=>
			float(18)
			["elevation_loss"]=>
			float(19)
			["surface"]=>
			string(4) "road"
			["weather"]=>
			string(5) "sunny"
			["feeling"]=>
			string(7) "awesome"
			["weather_id"]=>
			int(1)
			["feeling_id"]=>
			int(1)
			["surface_id"]=>
			int(1)
			["notes"]=>
			string(0) ""
			["page_url"]=>
			string(47) "/en/users/Example-User/sport-sessions/1111111"
			["create_route_url_class"]=>
			string(0) ""
			["create_route_url"]=>
			string(36) "/en/routes/new?sport_session=1111111"
			["map_url"]=>
			string(201) ""
			["date"]=>
			object(stdClass)#8 (6) {
				["year"]=>
				int(2011)
				["month"]=>
				string(2) "05"
				["day"]=>
				string(2) "19"
				["hour"]=>
				int(22)
				["minutes"]=>
				int(18)
				["seconds"]=>
				int(23)
			}
		}
	}

	
UPDATES
-------

I might write one or two things about this class on my blog [www.timo.in](http://www.timo.in) but the code itself will be maintained on GitHub only


LICENSE
-------

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