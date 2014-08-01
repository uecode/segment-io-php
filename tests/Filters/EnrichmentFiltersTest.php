<?php

namespace SegmentIO\Tests\Filters;

use SegmentIO\Filters\EnrichmentFilters;

/**
 * EnrichmentFiltersTest Class
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
class EnrichmentFiltersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Tests the EnrichmentFilters::generateMessageId() method
     */
    public function testGenerateMessageId()
    {
        // Validate its a properly formatted UUID v4
        $pattern = '/^[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12}$/i';
        $this->assertTrue((bool) preg_match($pattern, EnrichmentFilters::generateMessageId()));
    }

    /**
     * Tests the EnrichmentFilters::generateISODate($timestamp) method
     */
    public function testGenerateISODate()
    {
        // Validate its a properly formatted ISO 8601 DateTime string
        $pattern = '/^([\+-]?\d{4}(?!\d{2}\b))((-?)((0[1-9]|1[0-2])(\3([12]\d|0[1-9]|3[01]))?|W([0-4]\d|5[0-2])(-?[1-7])?|(00[1-9]|0[1-9]\d|[12]\d{2}|3([0-5]\d|6[1-6])))([T\s]((([01]\d|2[0-3])((:?)[0-5]\d)?|24\:?00)([\.,]\d+(?!:))?)?(\17[0-5]\d([\.,]\d+)?)?([zZ]|([\+-])([01]\d|2[0-3]):?([0-5]\d)?)?)?)?$/i';
        $this->assertTrue((bool) preg_match($pattern, EnrichmentFilters::generateISODate()));

        // Ensure that improperly formatted timestamps are handled
        $this->assertTrue((bool) preg_match($pattern, EnrichmentFilters::generateISODate('foo')));

        // Ensure that input timestamps match output
        $timestamp = time();
        $iso8601   = EnrichmentFilters::generateISODate($timestamp);
        $this->assertEquals($timestamp, strtotime($iso8601));
    }

    /**
     * Tests the EnrichmentFilters::generateDefaultContext(array $context) method
     */
    public function testGenerateDefaultContext()
    {
        $context = [
            'library' => [
                'name'    => 'analytics-php',
                'version' => \SegmentIO\Client::VERSION
            ]
        ];

        // Validate that the Default Context is returned properly
        $this->assertEquals($context, EnrichmentFilters::generateDefaultContext());

        // Validate that the Contexts are merged properly
        $this->assertEquals(
            array_merge($context, ['foo' => 'bar']),
            EnrichmentFilters::generateDefaultContext(['foo' => 'bar'])
        );
    }

    /**
     * Tests the EnrichmentFilters::enrichBatchOperations(array $operations) method
     */
    public function testEnrichBatchOperations()
    {
        $timestamp  = time();
        $operations = [
            ['event' => 'foo', 'bar' => 'baz'],
            ['event' => 'foo', 'bar' => 'baz', 'timestamp' => $timestamp]
        ];

        $enrichedOperations = EnrichmentFilters::enrichBatchOperations($operations);

        // Ensure that both MessageId and Timestamp are added
        $this->assertTrue(isset($enrichedOperations[0]['messageId']) && isset($enrichedOperations[0]['timestamp']));

        // Ensure that timestamps present in Operations are respected
        $this->assertEquals($timestamp, strtotime($enrichedOperations[1]['timestamp']));
    }
}
