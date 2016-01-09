# Runtastic PHP Library

Runtastic-PHP is a PHP library that allows you to connect to Runtastic ([www.runtastic.com](http://www.runtastic.com)) and get all your activities. This is a very dirty approach since Runtastic doesn't offer an official API.

The only 

## Getting Started

### Prerequisites

- PHP >= 5.2
- JSON and CURL (with SSL) PHP extensions.

### Installation

```sh
composer require timoschlueter/php-runtastic
```

### Basic Usage

```php
<?php

require __DIR__ . '/path/to/autoload.php';

use Runtastic\Runtastic;

$r = new Runtastic();
$r->setUsername("your@email.com")->setPassword("your@password");

$activities = $r->getActivities();
echo "Total Number of activities: " . count($activities) . PHP_EOL;

foreach ($activities as $activity) {
    echo $activity->id . PHP_EOL;
}
```

## Runtastic Class Methods

#### `setUsername()`

**[Mandatory]** Sets the username used for logging into Runtastic

#### `setPassword()`

**[Mandatory]** Sets the password used for logging into Runtastic

#### `setTimeout()`

Sets the connection timeout (in seconds) for cURL. `Defaults to 10`

#### `login()`

Logs into Runtastic (requires valid username and password)

#### `logout()`

Logs out and closes the cURL connection

#### `getUsername()`

Returns the Username used by Runtastic

#### `getUid()`

Returns the UID used by Runtastic

#### `getToken()`

Returns the session's token

#### `getActivities($iWeek = null, $iMonth = null, $iYear = null)`

Returns every activity in your Runtastic account as a RuntasticActivityList (usable as array) of objects. If
  - `$iWeek` is set, all activities within requested number of week will be returned (week starts on monday).
  - `$iMonth` is set, all activities within requested month will be returned.
  - `$iYear` is set, all activities within the requested year will be returned.

`$iWeek` and `$iMonth` can be used together with `$iYear`. if `$iYear` is null, the current year will be used for filtering. If you don't specify any argument, then, this function will return all activities.


### RuntasticActivityList Class Methods

The function `getActivities` of the Runtastic class that we saw before returns an RuntasticActivityList object. This class allows you to filter easily the activities using the following method:

#### `filterBy([])`

You can filter by every object defined in the Activity Object. This is what a typical activity object looks like:

```
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
```

So, for example, you can filter by like this:

```php
$activities = $r->getActivities()->filterBy(["type"=>"strolling", "weather"=>"good"]);
echo "Number of strolling activities with good weather: " . count($activities) . PHP_EOL;
```


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
