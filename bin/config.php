<?php declare(strict_types=1);

/**
 * Builder default configuration file
 *
 * Copyright 2017 Pim Koeman (pim@dataground.com)
 * Copyright 2017-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
    // The root path to place Library entites
    'libraryPath' => __DIR__ . '/../output/src',

    // The root namespace for library entities
    'libraryNamespacePrefix' => '\\DCarbone\\PHPFHIRGenerated',

    // The libxml opts to use for parsing version schema XSD's.
    'librarySchemaLibxmlOpts' => LIBXML_NONET | LIBXML_BIGLINES | LIBXML_PARSEHUGE | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOXMLDECL,

    // The root path to place test classes
    'testsPath' => __DIR__ . '/../output/tests',

    // Namespace prefix to apply to test classes.  The library namespace will be appended to this for all test classes.
    'testsNamespacePrefix' => '\\Tests',

    // Map of versions and configurations to generate.
    // Each entry in this map will grab the latest revision of that particular version.
    //
    // For a list of base versions, see here: https://www.hl7.org/fhir/directory.html
    'versions' => [
        [
            // Unique-to-you name of this version
            'name' => 'DSTU1',

            // Namespace to generate classes into.  This is nested under the value provided to rootNamespace.
            'namespace' => 'DSTU1',

            // Local path to un-compressed source schema files for this version
            'schemaPath' => __DIR__ . '/../input/DSTU1',

            // The default configuration for all instances of this version.  May be overridden during use.
            'defaultConfig' => [
                'unserializeConfig' => [
                    // Libxml options to use when unserializing types from XML
                    'libxmlOptMask' => 'LIBXML_NONET | LIBXML_BIGLINES | LIBXML_PARSEHUGE | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOXMLDECL',
                    // Maximum depth to allow when decoding JSON
                    'jsonDecodeMaxDepth' => 512,
                ],
                'serializeConfig' => [
                    // If true, will override the default xmlns value with the value provided in the rootXmlns key
                    'overrideSourceXMLNS' => false,
                    // If overrideSourceXmlns is true, this value will be used as the root xmlns value
                    'rootXMLNS' => 'http://hl7.org/fhir',
                    // Libxml options to use when serializing XHTML content
                    'xhtmlLibxmlOptMask' => 'LIBXML_NONET | LIBXML_BIGLINES | LIBXML_PARSEHUGE | LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOXMLDECL',
                ]
            ]
        ],
        [
            'name' => 'DSTU2',
            'schemaPath' => __DIR__ . '/../input/DSTU2',
        ],
        [
            'name' => 'STU3',
            'schemaPath' => __DIR__ . '/../input/STU3',
        ],
        [
            'name' => 'R4',
            'schemaPath' => __DIR__ . '/../input/R4',
        ],
        [
            'name' => 'R4B',
            'schemaPath' => __DIR__ . '/../input/R4B',
        ],
        [
            'name' => 'R5',
            'schemaPath' => __DIR__ . '/../input/R5',
        ],
    ],
];