Segment PHP Client
===================

[![Build Status](https://img.shields.io/travis/uecode/segment-io-php/master.svg?style=flat-square)](https://travis-ci.org/uecode/segment-io-php)
[![Quality Score](https://img.shields.io/scrutinizer/g/uecode/segment-io-php.svg?style=flat-square)](https://scrutinizer-ci.com/g/uecode/segment-io-php/)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/uecode/segment-io-php.svg?style=flat-square)](https://scrutinizer-ci.com/g/uecode/segment-io-php/)
[![Total Downloads](http://img.shields.io/packagist/dt/uecode/segment-io-php.svg?style=flat-square)](https://packagist.org/packages/uecode/segment-io-php)

This library provides a Web Service Client for the Segment.io HTTP API
using [Guzzle v4](http://guzzlephp.org).

### Basic Usage
```php
use SegmentIO\Client;

$client = new Client(['write_key' => $writeKey]);

// Identify the user - assuming, below, that you
// have a $user object from your database
$client->identify([
    'userId' => $user->getId(),
    'traits' => [
        'name' => $user->getName(),
        'email' => $user->getEmail()
    ]
]);

// Track an event
$client->track([
    'event' => 'Some Event Happened',
    'properties' => [
        'foo' => 'bar'
    ]
]);
```

### Configuration Options
The client accepts an array of configuration options:

Setting | Property Name | Description
--- | --- | ---
API Write Key | `write_key` | The Segment.io API Write Key
API Version | `version` | The API Version. Used to version the API (default: `v1`)
Batching | `batching` | A method of batching calls to the API to reduce latency of over the wire requests (supports: `request` or `file`)
Request Batching: Max Queue Size | `max_queue_size` | When using Request Batching, this is the total amount of Events to queue before flushing
Request Batching: Batch Size | `batch_size` | When using Request Batching, this is the total amount of Events sent in a single Request
File Batching: Log File | `log_file` | When using File Batching, this determines what file to log Events to

### Using Batching
By default, this client will attempt to queue all calls to the API and send them
out over a single batch request. Because of the blocking nature of PHP, this
method reduces the amount of time the Client has to wait for requests to the API.

Batching does not apply to the `import()` method on the client.

There are two methods of Batching Available:

#### Request Batching
**Note**: This is enabled by default.

When making calls to the API, the events will be placed into a queue and will be
flushed under one of two cases: when / if the `max_queue_size` is reached or at
the end of the PHP Request.

Changing the Client options for `max_queue_size` and `batch_size` will affect
how often the Client attempts to flush events.

#### File Batching
The file batching is a more performant method for making requests to Segment.io.

Each time a track or identify call is made, it will record that call to a log file.
The log file is then uploaded “out of band” by running the included `parse`
command.

You can change the location of the log file by using the `log_file` Client
configuration option. If a log_file is not specified, it will default to:
`/tmp/segment-io.log`.

To upload the Events from the log file to Segment.io, run the included `parse`
command:

    ./parse YOUR_WRITE_KEY --file /tmp/segment-io.log


### Tracking HTTP API Documentation
Documentation is available for the Tracking HTTP API at [segment.io/docs/tracking-api/](https://segment.io/docs/tracking-api/reference/).

License
-------
This software is released under the MIT License.  See the [license file](LICENSE.md) for details.

