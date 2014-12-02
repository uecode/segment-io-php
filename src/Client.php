<?php

namespace SegmentIO;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Collection;
use GuzzleHttp\Command\Guzzle\GuzzleClient;
use GuzzleHttp\Command\Guzzle\Description;
use GuzzleHttp\Command\Model;
use SegmentIO\Subscriber\BatchFileSubscriber;
use SegmentIO\Subscriber\BatchRequestSubscriber;

/**
 * Web Service Client for Segment.io
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 *
 * @method Model identify(array $data = [])
 * @method Model alias(array $data = [])
 * @method Model group(array $data = [])
 * @method Model track(array $data = [])
 * @method Model page(array $data = [])
 * @method Model screen(array $data = [])
 * @method Model import(array $data = [])
 */
class Client extends GuzzleClient
{
    /**
     * PHP Client Version
     */
    const VERSION = '1.0.1';

    /**
     * Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $defaults = [
            'write_key'      => null,
            'version'        => 'v1',
            'batching'       => 'request',
            'log_file'       => null,
            'max_queue_size' => 10000,
            'batch_size'     => 100
        ];

        // Create Configuration
        $config = Collection::fromConfig($config, $defaults, ['write_key', 'version', 'batching']);

        // Load versioned Service Description
        $description  = $this->loadServiceDescription(
            __DIR__ . '/Description/segment.io.%s.php', $config->get('version')
        );

        // Allow the Adapater to be set
        $httpConfig = $config->hasKey('adapter') ? ['adapter' => $config->get('adapter')] : [];

        // Create the Client
        parent::__construct(new HttpClient($httpConfig), $description, $config->toArray());

        // Set Basic Auth
        $this->getHttpClient()->setDefaultOption('auth', [$config->get('write_key'), null]);
        // Set the content type header to use "application/json" for all requests
        $this->getHttpClient()->setDefaultOption('headers', array('Content-Type' => 'application/json'));

        // Default the Version
        $this->setConfig('defaults/version', $this->getDescription()->getApiVersion());

        if ($config->get('batching') == 'request') {
            $this->getEmitter()->attach(new BatchRequestSubscriber([
                'max_queue_size' => $config->get('max_queue_size'),
                'batch_size'    => $config->get('batch_size')
            ]));
        }

        if ($config->get('batching') == 'file') {
            $this->getEmitter()->attach(new BatchFileSubscriber([
                'filename' => $config->get('log_file')
            ]));
        }
    }

    /**
     * Loads the Service Description from the given file path
     *
     * @param  string $filepath  The Service Description filepath
     * @param  string $version   The API Version
     *
     * @return Description
     */
    public function loadServiceDescription($filepath, $version)
    {
        $filepath    = sprintf($filepath, $version);
        $description = file_exists($filepath) ? include $filepath : null;

        if (!is_array($description)) {
            throw new \InvalidArgumentException('Invalid Service Description!');
        }

        return new Description($description);
    }
}
