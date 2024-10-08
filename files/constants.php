<?php declare(strict_types=1);

/*
 * Copyright 2016-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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
define('PHPFHIR_BIN_DIR', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'bin'));
define('PHPFHIR_DEFAULT_OUTPUT_DIR', realpath(PHPFHIR_ROOT_DIR . DIRECTORY_SEPARATOR . 'output'));
const PHPFHIR_OUTPUT_TMP_DIR = PHPFHIR_DEFAULT_OUTPUT_DIR . DIRECTORY_SEPARATOR . 'tmp';
const PHPFHIR_FHIR_VALIDATION_JAR = PHPFHIR_BIN_DIR . DIRECTORY_SEPARATOR . 'validator_cli.jar';

// format regex
const PHPFHIR_VARIABLE_NAME_REGEX = '{^[a-zA-Z_][a-zA-Z0-9_]*$}S';
const PHPFHIR_FUNCTION_NAME_REGEX = '{^[a-zA-Z_][a-zA-Z0-9_]*$}S';
const PHPFHIR_CLASSNAME_REGEX = '{^[a-zA-Z_][a-zA-Z0-9_]*$}S';
const PHPFHIR_NAMESPACE_REGEX = '{^[a-zA-Z][a-zA-Z0-9_]*(\\\[a-zA-Z0-9_]+)*[a-zA-Z0-9_]$}';

// type suffixes
const PHPFHIR_PRIMITIVE_SUFFIX = '-primitive';
const PHPFHIR_LIST_SUFFIX = '-list';

// html property
const PHPFHIR_XHTML_DIV = 'xhtml:div';

// raw type
const PHPFHIR_XHTML_TYPE_NAME = 'Xhtml';
const PHPFHIR_XHTML_TYPE_DESCRIPTION = 'XHTML type used in special cases';

// FHIR XML NS
const PHPFHIR_FHIR_XMLNS = 'https://hl7.org/fhir';

// XSDs
const PHPFHIR_SKIP_XML_XSD = 'xml.xsd';
const PHPFHIR_SKIP_XHTML_XSD = 'fhir-xhtml.xsd';
const PHPFHIR_SKIP_TOMBSTONE_XSD = 'tombstone.xsd';
const PHPFHIR_SKIP_ATOM_XSD_PREFIX = 'fhir-atom';
const PHPFHIR_SKIP_FHIR_XSD_PREFIX = 'fhir-';

// Properties
const PHPFHIR_UNLIMITED = -1;
const PHPFHIR_VALUE_PROPERTY_NAME = 'value';

// Rendering
const PHPFHIR_DOCBLOC_MAX_LENGTH = 80;
const PHPFHIR_NAMESPACE_TRIM_CUTSET = " \t\n\r\0\x0b\\/";
define('PHPFHIR_TEMPLATE_DIR', realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'template'));

// "file" directory.  will move at some point.
const PHPFHIR_TEMPLATE_FILE_DIR = PHPFHIR_TEMPLATE_DIR . DIRECTORY_SEPARATOR . 'file';

// Core interfaces, traits, and classes
const PHPFHIR_TEMPLATE_CORE_DIR = PHPFHIR_TEMPLATE_DIR . DIRECTORY_SEPARATOR . 'core';

// Type rendering
const PHPFHIR_TEMPLATE_TYPES_DIR = PHPFHIR_TEMPLATE_DIR . DIRECTORY_SEPARATOR . 'types';
const PHPFHIR_TEMPLATE_TYPES_PROPERTIES_DIR = PHPFHIR_TEMPLATE_TYPES_DIR . DIRECTORY_SEPARATOR . 'properties';
const PHPFHIR_TEMPLATE_TYPES_METHODS_DIR = PHPFHIR_TEMPLATE_TYPES_DIR . DIRECTORY_SEPARATOR . 'methods';
const PHPFHIR_TEMPLATE_TYPES_CONSTRUCTORS_DIR = PHPFHIR_TEMPLATE_TYPES_METHODS_DIR . DIRECTORY_SEPARATOR . 'constructors';
const PHPFHIR_TEMPLATE_TYPES_SERIALIZATION_DIR = PHPFHIR_TEMPLATE_TYPES_DIR . DIRECTORY_SEPARATOR . 'serialization';
const PHPFHIR_TEMPLATE_TYPES_VALIDATION_DIR = PHPFHIR_TEMPLATE_TYPES_DIR . DIRECTORY_SEPARATOR . 'validation';


// Fhir type test templates
const PHPFHIR_TEMPLATE_TYPE_TESTS_DIR = PHPFHIR_TEMPLATE_TYPES_DIR . DIRECTORY_SEPARATOR . 'tests';

// Core class names
const PHPFHIR_CLASSNAME_AUTOLOADER = 'PHPFHIRAutoloader';
const PHPFHIR_CLASSNAME_CONFIG = 'PHPFHIRConfig';
const PHPFHIR_CLASSNAME_RESPONSE_PARSER = 'PHPFHIRResponseParser';
const PHPFHIR_CLASSNAME_CONSTANTS = 'PHPFHIRConstants';
const PHPFHIR_CLASSNAME_TYPEMAP = 'PHPFHIRTypeMap';
const PHPFHIR_CLASSNAME_DEBUG_CLIENT = 'PHPFHIRDebugClient';
const PHPFHIR_CLASSNAME_DEBUG_CLIENT_RESPONSE = 'PHPFHIRDebugClientResponse';
const PHPFHIR_CLASSNAME_XML_WRITER = 'PHPFHIRXmlWriter';

// Core interface names
const PHPFHIR_INTERFACE_TYPE = 'PHPFHIRTypeInterface';
const PHPFHIR_INTERFACE_CONTAINED_TYPE = 'PHPFHIRContainedTypeInterface';
const PHPFHIR_INTERFACE_COMMENT_CONTAINER = 'PHPFHIRCommentContainerInterface';
const PHPFHIR_INTERFACE_PRIMITIVE_TYPE = 'PHPFHIRPrimitiveTypeInterface';

// Core traits
const PHPFHIR_TRAIT_COMMENT_CONTAINER = 'PHPFHIRCommentContainerTrait';
const PHPFHIR_TRAIT_VALIDATION_ASSERTIONS = 'PHPFHIRValidationAssertionsTrait';
const PHPFHIR_TRAIT_CHANGE_TRACKING = 'PHPFHIRChangeTrackingTrait';
const PHPFHIR_TRAIT_SOURCE_XMLNS = 'PHPFHIRSourceXmlNamespaceTrait';

// Core enums
const PHPFHIR_ENUM_CONFIG_KEY = 'PHPFHIRConfigKeyEnum';
const PHPFHIR_ENUM_TYPE = 'PHPFHIRTypeEnum';
const PHPFHIR_ENUM_API_FORMAT = 'PHPFHIRApiFormatEnum';
const PHPFHIR_ENUM_XML_LOCATION_ENUM = 'PHPFHIRXmlLocationEnum';

// validation constants
const PHPFHIR_VALIDATION_ENUM = 'enum';
const PHPFHIR_VALIDATION_ENUM_NAME = 'VALIDATE_ENUM';
const PHPFHIR_VALIDATION_MIN_LENGTH = 'min_length';
const PHPFHIR_VALIDATION_MIN_LENGTH_NAME = 'VALIDATE_MIN_LENGTH';
const PHPFHIR_VALIDATION_MAX_LENGTH = 'max_length';
const PHPFHIR_VALIDATION_MAX_LENGTH_NAME = 'VALIDATE_MAX_LENGTH';
const PHPFHIR_VALIDATION_PATTERN = 'pattern';
const PHPFHIR_VALIDATION_PATTERN_NAME = 'VALIDATE_PATTERN';
const PHPFHIR_VALIDATION_MIN_OCCURS = 'min_occurs';
const PHPFHIR_VALIDATION_MIN_OCCURS_NAME = 'VALIDATE_MIN_OCCURS';
const PHPFHIR_VALIDATION_MAX_OCCURS = 'max_occurs';
const PHPFHIR_VALIDATION_MAX_OCCURS_NAME = 'VALIDATE_MAX_OCCURS';

// static test type, namespace, and class values.
const PHPFHIR_TESTS_NAMESPACE_BASE = 'PHPFHIRTests';
const PHPFHIR_TESTS_NAMESPACE_UNIT = PHPFHIR_TESTS_NAMESPACE_BASE . '\\Unit';
const PHPFHIR_TESTS_NAMESPACE_INTEGRATION = PHPFHIR_TESTS_NAMESPACE_BASE . '\\Integration';
const PHPFHIR_TESTS_NAMESPACE_VALIDATION = PHPFHIR_TESTS_NAMESPACE_BASE . '\\Validation';
const PHPFHIR_TEST_CLASSNAME_CONSTANTS = PHPFHIR_CLASSNAME_CONSTANTS . 'Test';
const PHPFHIR_TEST_CLASSNAME_AUTOLOADER = PHPFHIR_CLASSNAME_AUTOLOADER . 'Test';
const PHPFHIR_TEST_CLASSNAME_TYPEMAP = PHPFHIR_CLASSNAME_TYPEMAP . 'Test';
const PHPFHIR_TEST_CLASSNAME_CONFIG = PHPFHIR_CLASSNAME_CONFIG . 'Test';

// date & time formats
const PHPFHIR_DATE_FORMAT_YEAR = 'Y';
const PHPFHIR_DATE_FORMAT_YEAR_MONTH = 'Y-m';
const PHPFHIR_DATE_FORMAT_YEAR_MONTH_DAY = 'Y-m-d';
const PHPFHIR_DATE_FORMAT_YEAR_MONTH_DAY_TIME = 'Y-m-d\\TH:i:s\\.uP';
const PHPFHIR_DATE_FORMAT_INSTANT = 'Y-m-d\\TH:i:s\\.uP';
const PHPFHIR_TIME_FORMAT = 'H:i:s';