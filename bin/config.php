<?php declare(strict_types=1);

/**
 * Builder default configuration file
 *
 * Copyright 2017 Pim Koeman (pim@dataground.com)
 * Copyright 2017-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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
    'versions' => [
        [
            'name' => 'DSTU1',
            'schemaPath' => __DIR__ . '/../input/DSTU1',
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

            // Optional: generate profile classes from FHIR profile packages (e.g. US Core).
            'profiles' => [
                // Namespace segment, relative to the version namespace, to place profile classes in.
                'namespace' => 'Profiles',
                'packages' => [
                    [
                        'name' => 'hl7.fhir.r4.core',
                        'source' => __DIR__ . '/../input/profiles/hl7.fhir.r4.core/package.tgz',
                        'namespace' => 'R4Core',
                        'strict' => true,
                    ],
                    [
                        'name' => 'hl7.terminology.r4',
                        'source' => __DIR__ . '/../input/profiles/hl7.fhir.terminology/hl7.terminology.r4.tgz',
                        'namespace' => 'TerminologyR4',
                        'dependencies' => [
                            'hl7.fhir.r4.core',
                        ],
                        'strict' => true,
                    ],
                    [
                        'name' => 'hl7.fhir.uv.extensions.r4',
                        'source' => __DIR__ . '/../input/profiles/hl7.fhir.uv.extensions.r4/package.tgz',
                        'namespace' => 'ExtensionsR4',
                        'dependencies' => [
                            'hl7.fhir.r4.core',
                            'hl7.terminology.r4',
                        ],
                        'strict' => true,
                    ],
                    [
                        'name' => 'hl7.fhir.uv.xver-r5.r4',
                        'source' => __DIR__ . '/../input/profiles/hl7.fhir.uv.xver-r5.r4/package.tgz',
                        'namespace' => 'XverR5R4',
                        'dependencies' => [
                            'hl7.fhir.r4.core',
                            'hl7.terminology.r4',
                            'hl7.fhir.uv.extensions.r4',
                        ],
                        'strict' => true,
                    ],
                    [
                        'name' => 'hl7.fhir.uv.tools.r4',
                        'source' => __DIR__ . '/../input/profiles/hl7.fhir.uv.tools.r4/package.tgz',
                        'namespace' => 'ToolsR4',
                        'dependencies' => [
                            'hl7.fhir.r4.core',
                            'hl7.terminology.r4',
                            'hl7.fhir.uv.extensions.r4',
                        ],
                        'strict' => true,
                    ],
                    [
                        'name' => 'hl7.fhir.uv.smart-app-launch',
                        'source' => __DIR__ . '/../input/profiles/hl7.fhir.uv.smart-app-launch/package.tgz',
                        'namespace' => 'SmartAppLaunch',
                        'dependencies' => [
                            'hl7.fhir.r4.core',
                            'hl7.terminology.r4',
                            'hl7.fhir.uv.extensions.r4',
                        ],
                        'strict' => true,
                    ],
                    [
                        'name' => 'us.cdc.phinvads',
                        'source' => __DIR__ . '/../input/profiles/us.cdc.phinvads/package.tgz',
                        'namespace' => 'PhinVads',
                        'dependencies' => [
                            'hl7.fhir.r4.core',
                        ],
                        'strict' => true,
                    ],
                    [
                        'name' => 'hl7.fhir.uv.sdc.r4',
                        'source' => __DIR__ . '/../input/profiles/hl7.fhir.uv.sdc.r4/package.r4.tgz',
                        'namespace' => 'Sdc',
                        'dependencies' => [
                            'hl7.fhir.r4.core',
                            'hl7.terminology.r4',
                            'hl7.fhir.uv.xver-r5.r4',
                            'hl7.fhir.uv.tools.r4',
                            'hl7.fhir.uv.extensions.r4',
                        ],
                        'strict' => true,
                    ],
                    [
                        // npm-style package name.  Required.
                        'name' => 'hl7.fhir.us.core',
                        // Path to the package .tgz file or an extracted package directory.  Required.
                        'source' => __DIR__ . '/../input/profiles/us_core_stu9/package.tgz',
                        // Namespace segment for this package.  Default is derived from name, ex: "UsCore".
                        'namespace' => 'UsCore',
                        // Names of other packages configured for this version that this one builds on.  They are generated first;
                        // this package resolves base profiles, extensions and ValueSets through them, and its classes
                        // may extend theirs.
                        'dependencies' => [
                            'hl7.fhir.r4.core',
                            'hl7.fhir.uv.sdc.r4',
                            'hl7.fhir.uv.smart-app-launch',
                            'us.cdc.phinvads',
                            'hl7.terminology.r4',
                            'hl7.fhir.uv.extensions.r4',
                            'hl7.fhir.uv.xver-r5.r4',
                        ],
                        // Fail on unresolvable profile elements instead of warning and skipping.
                        'strict' => true,
                    ],
                ],
            ],
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