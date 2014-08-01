<?php

namespace SegmentIO\Tests\Subscriber;

use GuzzleHttp\Adapter\MockAdapter;
use GuzzleHttp\Adapter\TransactionInterface;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use SegmentIO\Client;
use SegmentIO\Subscriber\BatchFileSubscriber;

/**
 * BatchFileSubscriberTest Class
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class BatchFileSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Client $client
     */
    protected $client;

    /**
     * @var BatchRequestSubscriber $subscriber
     */
    protected $subscriber;

    /**
     * Setup Method
     *
     * Creates an instance of BatchRequestSubscriber
     */
    public function setUp()
    {
        $adapter = new MockAdapter(function (TransactionInterface $trans) {
            $response = Stream::factory(json_encode(['success' => true]));

            return new Response(200, [], $response);
        });

        $this->client = new Client([
            'write_key'      => 123,
            'adapter'        => $adapter,
            'max_queue_size' => 1,
            'batching'       => 'file'
        ]);

        $this->subscriber = new BatchFileSubscriber([]);
    }

    /**
     * Tear Down the Test
     *
     * Used to explicitly test the BatchRequestSubscriber::__destruct() method
     */
    public function tearDown()
    {
        $file = $this->client->getConfig('file') ?: sys_get_temp_dir() . "/segment-io.log";
        if (file_exists($file)) {
            unlink($file);
        }

        unset($this->client);
        unset($this->subscriber);
    }

    /**
     * Testing the BatchRequestSubscriber::__construct() method
     */
    public function testConstructor()
    {
        $this->assertInstanceOf('SegmentIO\Subscriber\BatchFileSubscriber', $this->subscriber);
    }

    public function testBatchingRequests()
    {
        // Test that operations that do not allow 'batching'
        $response = $this->client->import(['batch' => [['event' => 'foo', 'properties' => ['bar' => 'baz']]]]);
        $this->assertEquals(['success' => true], $response->toArray());

        // Test that operations that allow 'batching'
        $response = $this->client->track(['userId' => 123, 'event' => 'foo', 'properties' => ['bar' => 'baz']]);
        $this->assertEquals(['success' => true, 'batched' => true], $response->toArray());
    }
}
