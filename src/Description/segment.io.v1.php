<?php
    return [
        'name' => 'Segment.io API',
        'description' => 'The Segment Tracking API lets you record analytics data from any website or application. The requests hit our servers, and we route your data to any integration you want!',
        'baseUrl'    => 'https://api.segment.io',
        'apiVersion' => 'v1',
        'operations' => [

            'identify' => [
                'uri' => '/{version}/identify',
                'description' => 'The identify method is how you tie one of your users and their actions to a recognizable `userId` and `traits`.',
                'httpMethod' => 'POST',
                'extends' => 'global',
                'responseModel' => 'Resource',
                'parameters' => [
                    'userId' => [
                        'type' => 'string',
                        'location' => 'json',
                        'required' => false
                    ],
                    'anonymousId'  => [
                        'type' => 'string',
                        'location' => 'json',
                        'required' => false
                    ],
                    'traits' => [
                        'type' => 'object',
                        'location' => 'json',
                        'required' => false,
                    ]
                ],
                'data' => [
                    'batching' => true
                ]
            ],

            'alias' => [
                'uri' => '/{version}/alias',
                'description' => 'The alias method is used to merge two user identities, effectively connecting two sets of user data as one.',
                'httpMethod' => 'POST',
                'extends' => 'global',
                'responseModel' => 'Resource',
                'parameters' => [
                    'previousId' => [
                        'type' => 'string',
                        'location' => 'json',
                        'required' => true
                    ],
                    'userId' => [
                        'type' => 'string',
                        'location' => 'json',
                        'required' => true
                    ],
                ],
                'data' => [
                    'batching' => true
                ]
            ],

            'group' => [
                'uri' => '/{version}/group',
                'description' => 'The group method lets you associate a user with a group, like an account or organization.',
                'httpMethod' => 'POST',
                'extends' => 'global',
                'responseModel' => 'Resource',
                'parameters' => [
                    'userId' => [
                        'type' => 'string',
                        'location' => 'json',
                        'required' => false
                    ],
                    'anonymousId' => [
                        'type' => 'string',
                        'location' => 'json',
                        'required' => false
                    ],
                    'groupId' => [
                        'type' => 'string',
                        'location' => 'json',
                        'required' => true
                    ],
                    'traits' => [
                        'type' => 'object',
                        'location' => 'json',
                        'required' => false,
                    ]
                ],
                'data' => [
                    'batching' => true
                ]
            ],

            'track' => [
                'uri' => '/{version}/track',
                'description' => 'The track method is how you record any actions your users perform.',
                'httpMethod' => 'POST',
                'extends' => 'global',
                'responseModel' => 'Resource',
                'parameters' => [
                    'userId' => [
                        'type' => 'string',
                        'location' => 'json',
                        'required' => false
                    ],
                    'anonymousId' => [
                        'type' => 'string',
                        'location' => 'json',
                        'required' => false
                    ],
                    'event' => [
                        'type' => 'string',
                        'location' => 'json',
                        'required' => true
                    ],
                    'properties' => [
                        'type' => 'object',
                        'location' => 'json',
                        'required' => true
                    ]
                ],
                'data' => [
                    'batching' => true
                ]
            ],

            'page' => [
                'uri' => '/{version}/page',
                'description' => 'The page and screen methods let your record whenever a user sees a page of your website or screen of your mobile app. They are exactly the same, except they connote either web or mobile.',
                'httpMethod' => 'POST',
                'extends' => 'global',
                'responseModel' => 'Resource',
                'parameters' => [
                    'userId' => [
                        'type' => 'string',
                        'location' => 'json',
                        'required' => false
                    ],
                    'anonymousId' => [
                        'type' => 'string',
                        'location' => 'json',
                        'required' => false
                    ],
                    'name' => [
                        'type' => 'string',
                        'location' => 'json',
                        'required' => false
                    ],
                    'category' => [
                        'type' => 'string',
                        'location' => 'json',
                        'required' => false
                    ],
                    'properties' => [
                        'type' => 'object',
                        'location' => 'json',
                        'required' => false
                    ]
                ],
                'data' => [
                    'batching' => true
                ]
            ],

            'screen' => [
                'uri' => '/{version}/screen',
                'description' => 'The page and screen methods let your record whenever a user sees a page of your website or screen of your mobile app. They are exactly the same, except they connote either web or mobile.',
                'httpMethod' => 'POST',
                'extends' => 'page',
                'responseModel' => 'Resource',
                'data' => [
                    'batching' => true
                ]
            ],

            'import' => [
                'uri' => '/{version}/import',
                'description' => 'The import method lets you send a series of identify, group, track, page and screen requests in a single batch, saving on outbound requests.',
                'httpMethod' => 'POST',
                'responseModel' => 'Resource',
                'parameters' => [
                    'version'  => [
                        'type' => 'string',
                        'location' => 'uri',
                        'required' => true
                    ],
                    'batch' => [
                        'type' => 'array',
                        'location' => 'json',
                        'required' => true,
                        'items' => [
                            'type' => 'object'
                        ],
                        'filters' => [['method' => 'SegmentIO\\Filters\\EnrichmentFilters::enrichBatchOperations', 'args' => ['@value']]]
                    ],
                    'context' => [
                        'type' => 'object',
                        'location' => 'json',
                        'required' => false,
                        'default' => [],
                        'filters' => [['method' => 'SegmentIO\\Filters\\EnrichmentFilters::generateDefaultContext', 'args' => ['@value']]]
                    ]
                ],
                'data' => [
                    'batching' => false
                ]
            ],

            'global' => [
                'description' => 'A parent operation which sets Segment.io specific fields on the event/request body.',
                'parameters' => [
                    'version'  => [
                        'type' => 'string',
                        'location' => 'uri',
                        'required' => true
                    ],
                    'messageId' => [
                        'type' => 'string',
                        'location' => 'json',
                        'required' => false,
                        'default' => '',
                        'filters' => ['SegmentIO\\Filters\\EnrichmentFilters::generateMessageId']
                    ],
                    'timestamp' => [
                        'type' => 'string',
                        'location' => 'json',
                        'required' => false,
                        'default' => '',
                        'filters' => [['method' => 'SegmentIO\\Filters\\EnrichmentFilters::generateISODate', 'args' => ['@value']]]
                    ],
                    'context' => [
                        'type' => 'object',
                        'location' => 'json',
                        'required' => false,
                        'default' => [],
                        'filters' => [['method' => 'SegmentIO\\Filters\\EnrichmentFilters::generateDefaultContext', 'args' => ['@value']]]
                    ],
                    'integrations' => [
                        'type' => 'object',
                        'location' => 'json',
                        'required' => false
                    ]
                ]
            ]
        ],
        'models' => [
            'Resource' => [
                'type' => 'object',
                'additionalProperties' => [
                    'location' => 'json',
                ]
            ]
        ]
    ];
