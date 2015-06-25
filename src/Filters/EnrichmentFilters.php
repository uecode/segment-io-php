<?php

namespace SegmentIO\Filters;

use SegmentIO\Client;

/**
 * EnrichmentFilters Class
 *
 * @author Keith Kirk <kkirk@undergroundelephant.com>
 */
abstract class EnrichmentFilters
{
    /**
     * Generates a UUID v4 as a Message Id
     *
     * @see https://gist.github.com/dahnielson/508447#file-uuid-php-L74
     *
     * @return string
     */
    public static function generateMessageId()
    {
        return sprintf("%04x%04x-%04x-%04x-%04x-%04x%04x%04x",
            // 32 bits for "time_low"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
            mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Generates an ISO 8601 datetime string
     *
     * @param  integer $timestamp Time the Event Occurred (Optional)
     *
     * @return string
     */
    public static function generateISODate($timestamp = null)
    {
        $timestamp = !is_null($timestamp) && is_numeric($timestamp)
            ? $timestamp
            : time();

        return date('c', $timestamp);
    }

    /**
     * Adds MessageIds and ISO 8601 formatted datetime strings to all batched operations
     *
     * @param  array  $operations The Batch of operations
     *
     * @return array
     */
    public static function enrichBatchOperations(array $operations = [])
    {
        foreach ($operations as &$op) {
            $timestamp = isset($op['timestamp']) ? $op['timestamp'] : null;

            $op = array_merge($op, [
                'messageId' => self::generateMessageId(),
                'timestamp' => self::generateISODate($timestamp)
            ]);
        }

        return $operations;
    }

    /**
     * Get the Default Context properties
     *
     * @param array $context
     *
     * @return array
     */
    public static function generateDefaultContext(array $context = [])
    {
        return array_merge($context, [
            'library' => [
                'name'    => 'analytics-php-guzzle',
                'version' => Client::VERSION
            ]
        ]);
    }
}
