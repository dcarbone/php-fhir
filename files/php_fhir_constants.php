<?php

define('PHPFHIR_ROOT_DIR', realpath(dirname(__DIR__)));
define('PHPFHIR_DEFAULT_OUTPUT_DIR', realpath(PHPFHIR_ROOT_DIR.'/output'));
define('PHPFHIR_TEMPLATE_DIR', realpath(PHPFHIR_ROOT_DIR.'/templates'));
define('PHPFHIR_DEFAULT_NAMESPACE', 'PHPFHIRGenerated');
define('PHPFHIR_ABSTRACT_PRIMITIVE_TYPE_CLASSNAME', 'AbstractPHPFHIRPrimitiveType');