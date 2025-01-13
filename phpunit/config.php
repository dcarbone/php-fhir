<?php

return array_merge(
    require __DIR__ . '/../bin/config.php',
    [
        'versions' => match (getenv('PHPFHIR_TEST_TARGET')) {
            'Core' => [],
            default => [
                [
                    'name' => getenv('PHPFHIR_TEST_TARGET'),
                    'schemaPath' => __DIR__ . '/../input/' . getenv('PHPFHIR_TEST_TARGET'),
                    'namespace' => 'Versions\\' . getenv('PHPFHIR_TEST_TARGET'),
                ],
            ],
        },
    ]
);
