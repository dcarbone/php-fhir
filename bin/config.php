<?php
/**
 * Generator default configuration file
 *
 * Copyright 2017 Pim Koeman (pim@dataground.com)
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
return [
    'schemaPath'  => __DIR__ . '/../input',
    'classesPath' => __DIR__ . '/../output',

    'versions' => [
        'DSTU1' => ['url' => 'http://hl7.org/fhir/DSTU1/fhir-all-xsd.zip', 'namespace' => '\\HL7\\FHIR\\DSTU1'],
        'DSTU2' => ['url' => 'http://hl7.org/fhir/DSTU2/fhir-all-xsd.zip', 'namespace' => '\\HL7\\FHIR\\DSTU2'],
        'STU3'  => ['url' => 'http://hl7.org/fhir/STU3/fhir-all-xsd.zip', 'namespace' => '\\HL7\\FHIR\\STU3'],
        '3.0.1' => ['url' => 'http://hl7.org/fhir/fhir-codegen-xsd.zip', 'namespace' => '\\HL7\\FHIR\\V301'],
        'Build' => ['url' => 'http://build.fhir.org/fhir-all-xsd.zip', 'namespace' => '\\HL7\\FHIR\\Build']
    ]
];
