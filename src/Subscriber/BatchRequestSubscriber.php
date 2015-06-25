<?php

namespace SegmentIO\Subscriber;

use GuzzleHttp\Command\Event\PreparedEvent;
use GuzzleHttp\Command\Event\ProcessEvent;
use GuzzleHttp\Command\Guzzle\DescriptionInterface;
use GuzzleHttp\Event\SubscriberInterface;
use SegmentIO\Client;

/**
 * BatchRequestSubscriber Class
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class BatchRequestSubscriber implements SubscriberInterface
{
    /**
     * Segment.io Client
     *
     * @var Client
     */
    private $client = null;

    /**
     * Webservice Description.
     *
     * @var DescriptionInterface
     */
    private $description;

    /**
     * Queue of Operations
     *
     * @var array
     */
    private $queue = [];

    /**
     * Determines the maximum size the queue is allowed to reach. New items pushed
     * to the queue will be ignored if this size is reached and cannot be flushed.
     * Defaults to 10000.
     *
     * @var integer
     */
    private $maxQueueSize = 10000;

    /**
     * Determines how many operations are sent to Segment.io in a single request.
     * Defaults to 100.
     *
     * @var integer
     */
    private $batchSize = 100;

    /**
     * Constructor
     *
     * @param DescriptionInterface $description
     * @param array                $options An array of configuration options
     */
    public function __construct(DescriptionInterface $description, array $options = [])
    {
        if (isset($options['max_queue_size'])) {
            $this->maxQueueSize = $options['max_queue_size'];
        }

        if (isset($options['batch_size'])) {
            $this->batchSize = $options['batch_size'];
        }
        $this->description = $description;
    }

    /**
     * Destructor
     *
     * Flushes any queued Operations
     */
    public function __destruct()
    {
        $this->flush();
        unset($this->client);
    }

    /**
     * Returns the Subscribed Events
     *
     * @return array
     */
    public function getEvents()
    {
        return [
            'prepared' => ['onPrepared', 'last'],
            'process'  => ['onProcess', 'first']
        ];
    }

    /**
     * Event to add Segment.io Specific data to the Event Messages
     *
     * @param PreparedEvent $event The PreparedEvent
     *
     * @return bool
     */
    public function onPrepared(PreparedEvent $event)
    {
        if (is_null($this->client)) {
            $this->client = $event->getClient();
        }

        $command   = $event->getCommand();
        $operation = $this->description->getOperation($command->getName());

        if (!$operation->getData('batching')) {
            return false;
        }

        $parameters = json_decode($event->getRequest()->getBody()->getContents(), true);
        $this->enqueue(array_merge($parameters, ['action' => $command->getName()]));

        $event->intercept(['success' => true, 'batched' => true]);

        return true;
    }

    /**
     * Stops propagation of ProcessEvents when using Batching
     *
     * @param  ProcessEvent $event The Process Event
     *
     * @return bool
     */
    public function onProcess(ProcessEvent $event)
    {
        $command   = $event->getCommand();
        $operation = $this->description->getOperation($command->getName());

        if (!$operation->getData('batching')) {
            return false;
        }

        $event->stopPropagation();

        return true;
    }

    /**
     * Adds User Actions to the Queue
     *
     * Will attempt to flush the queue if the size of the queue has reached
     * the Max Queue Size
     *
     * @param  array   $operation Operation as an associative array
     *
     * @return boolean
     */
    public function enqueue(array $operation)
    {
        if (count($this->queue) >= $this->maxQueueSize)
            return false;

        array_push($this->queue, $operation);

        if (count($this->queue) >= $this->maxQueueSize) {
            $this->flush();
        }

        return true;
    }

    /**
     * Flushes the queue by batching Import operations
     *
     * @return boolean
     */
    public function flush()
    {
        if (empty($this->queue)) {
            return false;
        }

        $operations = array_chunk($this->queue, $this->batchSize);
        foreach ($operations as $batch) {
            $this->client->import(['batch' => $batch]);
        }

        return true;
    }

}
