<?php

namespace SegmentIO\Subscriber;

use GuzzleHttp\Command\Event\PreparedEvent;
use GuzzleHttp\Command\Event\ProcessEvent;
use GuzzleHttp\Command\Guzzle\DescriptionInterface;
use GuzzleHttp\Event\SubscriberInterface;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

/**
 * BatchFileSubscriber Class
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class BatchFileSubscriber implements SubscriberInterface
{
    /**
     * Monolog Logger
     *
     * @var Logger
     */
    private $logger;

    /**
     * Webservice Description.
     *
     * @var DescriptionInterface
     */
    private $description;

    /**
     * Constructor
     *
     * @param DescriptionInterface $description
     * @param array                $options
     */
    public function __construct(DescriptionInterface $description, array $options = [])
    {
        $this->description = $description;

        if (!isset($options['filename'])) {
            $filename = sys_get_temp_dir() . "/segment-io.log";
        } else {
            $filename = $options['filename'];
        }

        $stream = new StreamHandler($filename, Logger::INFO);
        $stream->setFormatter(new LineFormatter("%message%\n", null));

        $this->logger = new Logger('segment-io');
        $this->logger->pushHandler($stream);
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
     * Adds User Actions to the Log
     *
     * @param  array   $operation Operation as an associative array
     *
     * @return boolean
     */
    public function enqueue(array $operation)
    {
        $this->logger->addInfo(json_encode($operation));

        return true;
    }
}
