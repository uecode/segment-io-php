<?php

namespace SegmentIO\Tests;

use Psr\Log\InvalidArgumentException;
use SegmentIO\Client;
use PHPUnit\Framework\TestCase;

/**
 * ClientTest Class
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class ClientTest extends TestCase
{
    /**
     * @var Client $client
     */
    protected $client;

    /**
     * Setup Method
     *
     * Create an instance of Segment\Client
     */
    public function setUp()
    {
        $this->client = new Client(['write_key' => 123]);
    }

    /**
     * Testing the Client::__construct() method
     */
    public function testConstructor()
    {
        $this->assertInstanceOf('SegmentIO\Client', $this->client);
    }

    /**
     * Testing the Client::loadServiceDescription($filepath, $version) method
     *
     * Should fail on invalid types or file paths
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Invalid Service Description!
     */
    public function testLoadingServiceDescription()
    {
        // Test with a valid Service Description
        $description = $this->client->loadServiceDescription(
            __DIR__ . '/../src/Description/segment.io.%s.php', $this->client->getConfig('version')
        );
        $this->assertInstanceOf('GuzzleHttp\Command\Guzzle\Description', $description);

        // Test with a valid file and bad response
        $this->client->loadServiceDescription(
            __DIR__ . '/Description/invalid.service.description.%s.php', 'v1'
        );

        // Test with a invalid file path
        $this->client->loadServiceDescription(
            'foo.%.bar', 'v1'
        );
    }
}
