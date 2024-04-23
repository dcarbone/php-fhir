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
    'schemaPath'  => __DIR__ . '/../input/',

    // The path to place generated type class files
    'classesPath' => __DIR__ . '/../output/',

    // If true, will use a noop null logger
    'silent'      => false,

    // If true, will skip generation of test classes
    'skipTests'   => false,

    // If you wish to specify alternative libxml opts, do so here.
    'libxmlOpts'  => LIBXML_NONET | LIBXML_PARSEHUGE | LIBXML_COMPACT,

    // Map of versions and configurations to generate
    // Each entry in this map will grab the latest revision of that particular version.  If you wish to use a specific
    // version, please see https://www.hl7.org/fhir/directory.html
    'versions'    => [
        'DSTU1' => [
            // Source URL
            'url'       => 'https://hl7.org/fhir/DSTU1/fhir-all-xsd.zip',
            // Namespace to prefix the generated classes with
            'namespace' => '\\HL7\\FHIR\\DSTU1',
        ],
        'DSTU2' => [
            'url'          => 'https://hl7.org/fhir/DSTU2/fhir-all-xsd.zip',
            'namespace'    => '\\HL7\\FHIR\\DSTU2',
            'testEndpoint' => 'https://hapi.fhir.org/baseDstu2',
        ],
        'STU3'  => [
            'url'          => 'https://hl7.org/fhir/STU3/fhir-all-xsd.zip',
            'namespace'    => '\\HL7\\FHIR\\STU3',
            'testEndpoint' => 'https://hapi.fhir.org/baseDstu3',
        ],
        'R4'    => [
            'url'          => 'https://hl7.org/fhir/R4/fhir-all-xsd.zip',
            'namespace'    => '\\HL7\\FHIR\\R4',
            'testEndpoint' => 'https://hapi.fhir.org/baseR4',
        ],
        'R5' => [
            'url'          => 'https://hl7.org/fhir/R5/fhir-all-xsd.zip',
            'namespace'    => '\\HL7\\FHIR\\R5',
            'testEndpoint' => 'https://hapi.fhir.org/baseR5',
        ]

        //        'Build' => [
        //            'url'       => 'https://build.fhir.org/fhir-all-xsd.zip',
        //            'namespace' => '\\HL7\\FHIR\\Build',
        //        ],
    ],
];