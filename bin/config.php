<?php declare(strict_types=1);

/**
 * Generator default configuration file
 *
 * Copyright 2017 Pim Koeman (pim@dataground.com)
 * Copyright 2017-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
return [
    // The path to look look for and optionally download source XSD files to
    'schemaPath' => __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'input' . DIRECTORY_SEPARATOR,

    // The path to place generated files
    'classesPath' => __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'output' . DIRECTORY_SEPARATOR,

    // The root namespace for all generated classes
    'rootNamespace' => '\\DCarbone\\PHPFHIRGenerated',

    // If true, will use a noop null logger
    'silent' => false,

    // If true, will skip generation of test classes
    'skipTests' => false,

    // If you wish to specify alternative libxml opts, do so here.
    'libxmlOpts' => LIBXML_NONET | LIBXML_BIGLINES | LIBXML_PARSEHUGE | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOXMLDECL,

    // Map of versions and configurations to generate.
    // Each entry in this map will grab the latest revision of that particular version.
    //
    // For a list of base versions, see here: https://www.hl7.org/fhir/directory.html
    'versions' => [
        'DSTU1' => [
            // Source URL
            'sourceUrl' => 'https://hl7.org/fhir/DSTU1/fhir-all-xsd.zip',
            // Namespace
            'namespace' => 'Versions\\DSTU1',
            // If defined, enables integration and validation test generation against the provided endpoint.
            'testEndpoint' => '',
        ],
        'DSTU2' => [
            'sourceUrl' => 'https://hl7.org/fhir/DSTU2/fhir-all-xsd.zip',
            'namespace' => 'Versions\\DSTU2',
            'testEndpoint' => 'https://hapi.fhir.org/baseDstu2',
        ],
        'STU3' => [
            'sourceUrl' => 'https://hl7.org/fhir/STU3/fhir-all-xsd.zip',
            'namespace' => 'Versions\\STU3',
            'testEndpoint' => 'https://hapi.fhir.org/baseDstu3',
        ],
        'R4' => [
            'sourceUrl' => 'https://hl7.org/fhir/R4/fhir-all-xsd.zip',
            'namespace' => 'Versions\\R4',
            'testEndpoint' => 'https://hapi.fhir.org/baseR4',
        ],
        'R5' => [
            'sourceUrl' => 'https://hl7.org/fhir/R5/fhir-all-xsd.zip',
            'namespace' => 'Versions\\R5',
            'testEndpoint' => 'https://hapi.fhir.org/baseR5',
        ]

        //        'Build' => [
        //            'url'       => 'https://build.fhir.org/fhir-all-xsd.zip',
        //            'namespace' => '\\HL7\\FHIR\\Build',
        //        ],
    ],
];