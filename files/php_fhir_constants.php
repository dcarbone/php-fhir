<?php

/*
 * Copyright 2016-2018 Daniel Carbone (daniel.p.carbone@gmail.com)
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

// conf defaults
define('PHPFHIR_ROOT_DIR', realpath(dirname(__DIR__)));
define('PHPFHIR_DEFAULT_OUTPUT_DIR', realpath(PHPFHIR_ROOT_DIR . '/output'));
define('PHPFHIR_DEFAULT_NAMESPACE', 'PHPFHIRGenerated');

// format regex
define('PHPFHIR_VARIABLE_NAME_REGEX', '{^[a-zA-Z_][a-zA-Z0-9_]*$}S');
define('PHPFHIR_FUNCTION_NAME_REGEX', '{^[a-zA-Z_][a-zA-Z0-9_]*$}S');
define('PHPFHIR_CLASSNAME_REGEX', '{^[a-zA-Z_][a-zA-Z0-9_]*$}S');
define('PHPFHIR_NAMESPACE_REGEX', '{^[a-zA-Z][a-zA-Z0-9_]*(\\\[a-zA-Z0-9_]+)*[a-zA-Z0-9_]$}');

// FHIR XML NS
define('FHIR_XMLNS', 'http://hl7.org/fhir');

// Types
define('PHPFHIR_TYPE_RESOURCE_CONTAINER', 'ResourceContainer');
define('PHPFHIR_TYPE_RESOURCE_INLINE', 'Resource.Inline');
define('PHPFHIR_TYPE_RESOURCE', 'Resource');
define('PHPFHIR_TYPE_DOMAIN_RESOURCE', 'DomainResource');
define('PHPFHIR_TYPE_HTML', 'html');
define('PHPFHIR_TYPE_UNDEFINED', 'undefined');

// Properties
define('PHPFHIR_PROPERTY_OCCURS_UNLIMITED', -1);

// Generation
define(
    'PHPFHIR_DEFAULT_JSON_SERIALIZE_HEADER',
    <<<PHP
    /**
     * @return mixed
     */
    public function jsonSerialize()
    {

PHP
);
define(
    'PHPFHIR_DEFAULT_XML_SERIALIZE_HEADER',
    <<<PHP
    /**
     * @param bool \$returnSXE
     * @param null|\SimpleXMLElement \$sxe
     * @return string|\SimpleXMLElement
     */
    public function xmlSerialize(\$returnSXE = false, \SimpleXMLElement \$sxe = null)
    {

PHP
);