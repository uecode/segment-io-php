<?php

namespace SegmentIO\Subscriber;

use GuzzleHttp\Command\Event\PrepareEvent;
use GuzzleHttp\Command\Event\ProcessEvent;
use GuzzleHttp\Command\Event\CommandErrorEvent;
use GuzzleHttp\Command\Model;
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
     * Constructor
     *
     * @param array $options An array of configuration options
     */
    public function __construct(array $options = [])
    {
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
            'prepare' => ['onPrepare', 'last'],
            'process' => ['onProcess', 'first']
        ];
    }

    /**
     * Event to add Segment.io Specific data to the Event Messages
     *
     * @param PrepareEvent $event The PrepareEvent
     */
    public function onPrepare(PrepareEvent $event)
    {
        $command = $event->getCommand();
        $name    = $command->getName();

        if (!$command->getOperation()->getData('batching')) {
            return false;
        }

        $parameters = json_decode($event->getRequest()->getBody()->getContents(), true);
        $this->enqueue(array_merge($parameters, ['action' => $command->getName()]));

        $event->setResult(new Model(['success' => true, 'batched' => true]));

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
        if (!$event->getCommand()->getOperation()->getData('batching')) {
            return false;
        }

        return $event->stopPropagation();
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
