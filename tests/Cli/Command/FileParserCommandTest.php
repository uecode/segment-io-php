<?php

namespace SegmentIO\Tests\Cli;

use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use SegmentIO\Cli\Command\FileParserCommand;
use Symfony\Component\Console\Tester\CommandTester;
use PHPUnit\Framework\TestCase;

/**
 * FileParserCommandTest Class
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class FileParserCommandTest extends TestCase
{

    /**
     * Create logs used for testing
     */
    public function setUp()
    {
        $this->createLog(sys_get_temp_dir() . "/segment-io-test-empty.log", [[]]);
        $this->createLog(
            sys_get_temp_dir() . "/segment-io-test.log",
            [['userId' => 123, 'event' => 'foo', 'properties' => ['bar' => 'baz']]]
        );
    }

    /**
     * Clean up any left over logs files
     */
    public function tearDown()
    {
        foreach(glob(sys_get_temp_dir() . "/segment-io-*.log") as $file) {
            unlink($file);
        }
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage Not enough arguments
     */
    public function testInvalidWriteKey()
    {
        $tester = new CommandTester(new FileParserCommand());
        $tester->execute([]);
    }

    /**
     * @expectedException        RuntimeException
     * @expectedExceptionMessage The specified file does not exist!
     */
    public function testInvalidFile()
    {
        $tester = new CommandTester(new FileParserCommand());
        $tester->execute([
            'write_key' => 123,
            '--file' => '/tmp/z-segment-io.log'
        ]);
    }

    public function testNoEventsInLog()
    {
        $tester = new CommandTester(new FileParserCommand());
        $tester->execute([
            'write_key' => 123,
            '--file'    => sys_get_temp_dir() . "/segment-io-test-empty.log",
            '--debug'   => true
        ]);

        $this->assertEquals('Found 0 events in the log to Send', trim($tester->getDisplay()));
    }

    public function testSendingLoggedEvents()
    {
        $tester = new CommandTester(new FileParserCommand());
        $tester->execute([
            'write_key' => 123,
            '--file'    => sys_get_temp_dir() . "/segment-io-test.log",
            '--debug'   => true
        ]);

        $this->assertEquals(
            'Found 1 events in the log to SendSent 1 batches to Segment.io',
            str_replace("\n", '', $tester->getDisplay())
        );
    }

    /**
     * Creates a Log with a single entry
     *
     * @param  string $filename The log file
     * @param  array  $entries  The log entries
     */
    private function createLog($filename, array $entries = [])
    {
        $stream = new StreamHandler($filename, Logger::INFO);
        $stream->setFormatter(new LineFormatter("%message%\n", null));

        $logger = new Logger('segment-io');
        $logger->pushHandler($stream);

        foreach($entries as $entry) {
            $logger->addInfo(json_encode($entry));
        }
    }
}
