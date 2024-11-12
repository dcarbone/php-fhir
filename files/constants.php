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

// Core interfaces, traits, and classes
const PHPFHIR_TEMPLATE_CORE_DIR = PHPFHIR_TEMPLATE_DIR . DIRECTORY_SEPARATOR . 'core';

// Version interfaces, traits, and enums
const PHHPFHIR_TEMPLATE_VERSIONS_DIR = PHPFHIR_TEMPLATE_DIR . DIRECTORY_SEPARATOR . 'versions';
const PHPFHIR_TEMPLATE_VERSIONS_CORE_DIR = PHHPFHIR_TEMPLATE_VERSIONS_DIR . DIRECTORY_SEPARATOR . 'core';

// Version type rendering
const PHPFHIR_TEMPLATE_VERSION_TYPES_DIR = PHHPFHIR_TEMPLATE_VERSIONS_DIR . DIRECTORY_SEPARATOR . 'types';
const PHPFHIR_TEMPLATE_VERSION_TYPES_PROPERTIES_DIR = PHPFHIR_TEMPLATE_VERSION_TYPES_DIR . DIRECTORY_SEPARATOR . 'properties';
const PHPFHIR_TEMPLATE_VERSION_TYPES_METHODS_DIR = PHPFHIR_TEMPLATE_VERSION_TYPES_DIR . DIRECTORY_SEPARATOR . 'methods';
const PHPFHIR_TEMPLATE_VERSION_TYPES_CONSTRUCTORS_DIR = PHPFHIR_TEMPLATE_VERSION_TYPES_METHODS_DIR . DIRECTORY_SEPARATOR . 'constructors';
const PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR = PHPFHIR_TEMPLATE_VERSION_TYPES_DIR . DIRECTORY_SEPARATOR . 'serialization';
const PHPFHIR_TEMPLATE_VERSION_TYPES_VALIDATION_DIR = PHPFHIR_TEMPLATE_VERSION_TYPES_DIR . DIRECTORY_SEPARATOR . 'validation';


// Fhir type test templates
const PHPFHIR_TEMPLATE_VERSION_TYPE_TESTS_DIR = PHPFHIR_TEMPLATE_VERSION_TYPES_DIR . DIRECTORY_SEPARATOR . 'tests';

// Core class names
const PHPFHIR_CLASSNAME_AUTOLOADER = 'Autoloader';
const PHPFHIR_CLASSNAME_CONFIG = 'Config';
const PHPFHIR_CLASSNAME_RESPONSE_PARSER = 'ResponseParser';
const PHPFHIR_CLASSNAME_CONSTANTS = 'Constants';
const PHPFHIR_CLASSNAME_API_CLIENT = 'ApiClient';
const PHPFHIR_CLASSNAME_API_CLIENT_REQUEST = 'ApiClientRequest';
const PHPFHIR_CLASSNAME_API_CLIENT_RESPONSE = 'ApiClientResponse';
const PHPFHIR_CLASSNAME_XML_WRITER = 'XmlWriter';

// Core interface names
const PHPFHIR_INTERFACE_TYPE_MAP = 'TypeMapInterface';
const PHPFHIR_INTERFACE_FHIR_VERSION = 'VersionInterface';
const PHPFHIR_INTERFACE_TYPE = 'TypeInterface';
const PHPFHIR_INTERFACE_CONTAINED_TYPE = 'ContainedTypeInterface';
const PHPFHIR_INTERFACE_COMMENT_CONTAINER = 'CommentContainerInterface';
const PHPFHIR_INTERFACE_PRIMITIVE_TYPE = 'PrimitiveTypeInterface';

// Core traits
const PHPFHIR_TRAIT_COMMENT_CONTAINER = 'CommentContainerTrait';
const PHPFHIR_TRAIT_VALIDATION_ASSERTIONS = 'ValidationAssertionsTrait';
const PHPFHIR_TRAIT_SOURCE_XMLNS = 'SourceXmlNamespaceTrait';

// Core enums
const PHPFHIR_ENUM_CONFIG_KEY = 'ConfigKeyEnum';
const PHPFHIR_ENUM_TYPE = 'TypeEnum';
const PHPFHIR_ENUM_API_FORMAT = 'ApiFormatEnum';
const PHPFHIR_ENUM_XML_LOCATION_ENUM = 'XmlLocationEnum';
const PHPFHIR_ENUM_API_RESOURCE_LIST = 'ApiResourceListEnum';
const PHPFHIR_ENUM_API_SORT = 'ApiSortEnum';

// Version Constants
const PHPFHIR_CLASSNAME_VERSION = 'Version';
const PHPFHIR_CLASSNAME_VERSION_TYPEMAP = 'VersionTypeMap';
const PHPFHIR_CLASSNAME_VERSION_CONSTANTS = 'VersionConstants';


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
const PHPFHIR_TESTS_NAMESPACE_BASE = 'Tests';
const PHPFHIR_TESTS_NAMESPACE_UNIT = PHPFHIR_TESTS_NAMESPACE_BASE . '\\Unit';
const PHPFHIR_TESTS_NAMESPACE_INTEGRATION = PHPFHIR_TESTS_NAMESPACE_BASE . '\\Integration';
const PHPFHIR_TESTS_NAMESPACE_VALIDATION = PHPFHIR_TESTS_NAMESPACE_BASE . '\\Validation';
const PHPFHIR_TEST_CLASSNAME_CONSTANTS = PHPFHIR_CLASSNAME_CONSTANTS . 'Test';
const PHPFHIR_TEST_CLASSNAME_AUTOLOADER = PHPFHIR_CLASSNAME_AUTOLOADER . 'Test';
const PHPFHIR_TEST_CLASSNAME_TYPEMAP = PHPFHIR_CLASSNAME_VERSION_TYPEMAP . 'Test';
const PHPFHIR_TEST_CLASSNAME_CONFIG = PHPFHIR_CLASSNAME_CONFIG . 'Test';

// date & time formats
const PHPFHIR_DATE_FORMAT_YEAR = 'Y';
const PHPFHIR_DATE_FORMAT_YEAR_MONTH = 'Y-m';
const PHPFHIR_DATE_FORMAT_YEAR_MONTH_DAY = 'Y-m-d';
const PHPFHIR_DATE_FORMAT_YEAR_MONTH_DAY_TIME = 'Y-m-d\\TH:i:s\\.uP';
const PHPFHIR_DATE_FORMAT_INSTANT = 'Y-m-d\\TH:i:s\\.uP';
const PHPFHIR_TIME_FORMAT = 'H:i:s';