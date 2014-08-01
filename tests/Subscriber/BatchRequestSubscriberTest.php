<?php

namespace SegmentIO\Tests\Subscriber;

use GuzzleHttp\Adapter\MockAdapter;
use GuzzleHttp\Adapter\TransactionInterface;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use SegmentIO\Client;
use SegmentIO\Subscriber\BatchRequestSubscriber;

/**
 * BatchRequestSubscriberTest Class
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class BatchRequestSubscriberTest extends \PHPUnit_Framework_TestCase
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
            'batching'       => 'request',
            'max_queue_size' => 1
        ]);

        $this->subscriber = new BatchRequestSubscriber(['max_queue_size' => 1, 'batch_size' => 1]);
    }

    /**
     * Tear Down the Test
     *
     * Used to explicitly test the BatchRequestSubscriber::__destruct() method
     */
    public function tearDown()
    {
        unset($this->subscriber);
    }

    /**
     * Testing the BatchRequestSubscriber::__construct() method
     */
    public function testConstructor()
    {
        $this->assertInstanceOf('SegmentIO\Subscriber\BatchRequestSubscriber', $this->subscriber);
    }

    public function testBatchingRequests()
    {
        // Test that operations that do not allow 'batching'
        $response = $this->client->import(['batch' => [['event' => 'foo', 'properties' => ['bar' => 'baz']]]]);
        $this->assertEquals(['success' => true], $response->toArray());

        // Test that operations that allow 'batching'
        $response = $this->client->track(['event' => 'foo', 'properties' => ['bar' => 'baz']]);
        $this->assertEquals(['success' => true, 'batched' => true], $response->toArray());
    }

    public function testBatchingFlushesQueueAutomatically()
    {
        $events = [
            ['event' => 'foo', 'properties' => ['bar' => 'baz']],
            ['event' => 'foo', 'properties' => ['bar' => 'baz']]
        ];

        foreach ($events as $event) {
            $this->client->track($event);
        }
    }
}
