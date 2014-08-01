<?php

namespace SegmentIO\Cli\Command;

use GuzzleHttp\Adapter\MockAdapter;
use GuzzleHttp\Adapter\TransactionInterface;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use SegmentIO\Client;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * FileParserCommand Class
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class FileParserCommand extends Command
{
    /**
     * {@inhertDoc}
     */
    protected function configure()
    {
        $this
            ->setName('parse')
            ->setDescription('Parses a log file and sends logged events to the Segment.io API')
            ->addArgument('write_key', InputArgument::REQUIRED, 'The Segment.io API Write Key', null)
            ->addOption(
               'file',
               null,
               InputOption::VALUE_OPTIONAL,
               'The file to parse for Segment.io events',
               sys_get_temp_dir() . "/segment-io.log"
            )
            ->addOption(
                'debug',
                null,
                InputOption::VALUE_NONE,
                'Debug mode keeps logs from being removed and does not send the data to Segment.io'
            );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $key   = $input->getArgument('write_key');
        $file  = $input->getOption('file');
        $debug = $input->getOption('debug');

        $client     = $this->createClient($key, $debug);
        $filesystem = new Filesystem();

        if (!$filesystem->exists($file)) {
            throw new \RuntimeException('The specified file does not exist!');
        }

        $temp = sys_get_temp_dir() . sprintf("/segment-io-%s.log", uniqid());
        $filesystem->rename($file, $temp);

        $events = $this->getEvents($temp);
        $output->writeln(sprintf("<info>Found %s events in the log to Send</info>", sizeof($events)));
        if (!sizeof($events)) {
            return 0;
        }

        $batches = array_chunk(array_filter($events), 100);
        foreach ($batches as $batch) {
            $client->import(['batch' => $batch]);
        }

        $output->writeln(sprintf("<comment>Sent %s batches to Segment.io</comment>", sizeof($batches)));

        $filesystem->remove($temp);

        return 0;
    }


    /**
     * Parses the Log file and returns an array of events to send to Segment.io
     *
     * @param  string $file The log file
     *
     * @return array
     */
    private function getEvents($file)
    {
        $contents = file_get_contents($file);
        $events   = explode("\n", $contents, -1);

        $events = array_map(function($event) {
            $event = json_decode($event, true);
            if (!empty($event)) {
                return $event;
            }
        }, $events);

        return array_filter($events);
    }

    /**
     * Creates a SegmentIO\Client
     *
     * @param  string  $key   The Segment.io API Write Key
     * @param  boolean $debug Whether or not to use a MockAdapater
     *
     * @return Client
     */
    private function createClient($key, $debug = false)
    {
        $parameters = ['write_key' => $key, 'batching' => false];

        if ($debug) {
            $adapter = new MockAdapter(function (TransactionInterface $trans) {
                $response = Stream::factory(json_encode(['success' => true]));

                return new Response(200, [], $response);
            });
            $parameters['adapter'] = $adapter;
        }

        return new Client($parameters);
    }
}
