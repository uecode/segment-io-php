<?php

namespace SegmentIO\Tests\Subscriber;

use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\Mock;
use SegmentIO\Client;
use SegmentIO\Subscriber\BatchFileSubscriber;
use PHPUnit\Framework\TestCase;

/**
 * BatchFileSubscriberTest Class
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class BatchFileSubscriberTest extends TestCase
{
    /**
     * @var Client $client
     */
    protected $client;

    /**
     * @var BatchFileSubscriber $subscriber
     */
    protected $subscriber;

    /**
     * Setup Method
     *
     * Creates an instance of BatchFileSubscriber
     */
    public function setUp()
    {
        $stream = Stream::factory(json_encode(['success' => true]));
        $mock   = new Mock([
            new Response(200, [], $stream),
        ]);

        $this->client = new Client([
            'write_key'      => 123,
            'max_queue_size' => 1,
            'batching'       => 'file'
        ]);

        $this->client->getEmitter()->attach($mock);

        $this->subscriber = new BatchFileSubscriber($this->client->getDescription(), []);
    }

    /**
     * Tear Down the Test
     *
     * Used to explicitly test the BatchFileSubscriber::__destruct() method
     */
    public function tearDown()
    {
        $file = $this->client->getConfig('file') ?: sys_get_temp_dir() . "/segment-io.log";
        if (file_exists($file)) {
            unlink($file);
        }

        $file = sys_get_temp_dir() . "/segment-io-test2.log";
        if (file_exists($file)) {
            unlink($file);
        }

        unset($this->client);
        unset($this->subscriber);
    }

    public function testBatchingRequests()
    {
        // Test that operations that do not allow 'batching'
        $response = $this->client->import(['batch' => [['event' => 'foo', 'properties' => ['bar' => 'baz']]]]);
        $this->assertEquals(['success' => true], $response);

        // Test that operations that allow 'batching'
        $response = $this->client->track(['userId' => 123, 'event' => 'foo', 'properties' => ['bar' => 'baz']]);
        $this->assertEquals(['success' => true, 'batched' => true], $response);
    }

    /**
     * Testing the BatchRequestSubscriber::__construct() method
     */
    public function testConstructor()
    {
        $this->assertInstanceOf('SegmentIO\Subscriber\BatchFileSubscriber', $this->subscriber);

    }

    /**
     * Tests that the correct log file is used
     */
    public function testLogFile()
    {
        $this->client->track(['userId' => 123, 'event' => 'foo', 'properties' => ['bar' => 'baz']]);
        $this->assertTrue(file_exists(sys_get_temp_dir() . "/segment-io.log"));

        $file = sys_get_temp_dir() . "/segment-io-test2.log";
        $this->client = new Client([
            'write_key'      => 123,
            'max_queue_size' => 1,
            'batching'       => 'file',
            'log_file'       => sys_get_temp_dir() . "/segment-io-test2.log",
        ]);

        $this->client->track(['userId' => 123, 'event' => 'foo', 'properties' => ['bar' => 'baz']]);
        $this->assertTrue(file_exists(sys_get_temp_dir() . "/segment-io-test2.log"));
    }
}
