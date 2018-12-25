<?php

namespace DCarbone\PHPFHIR\Utilities;

use DCarbone\PHPFHIR\Config\VersionConfig;
use DCarbone\PHPFHIR\Definition;

/**
 * Class TypeMapUtils
 * @package DCarbone\PHPFHIR\Utilities
 */
abstract class TypeMapUtils
{
    const VARS = <<<PHP
    const RESOURCE_TYPE_FIELD = 'resourceType';
    
    /** @var array */
    private static \$_classMap = %s;

PHP;

    const FUNC_GET_TYPE_CLASS = <<<PHP

PHP;


    public function buildTypeMap(VersionConfig $config, Definition $definition)
    {
    }
}