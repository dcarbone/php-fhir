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
    private static \$_typeMap = %s;

PHP;

    const FUNCS = <<<PHP
    /**
     * @var string \$fhirTypeName
     * @return string|null
     */
    public static function getClassForType(\$fhirTypeName)
    {
        if (isset(self::\$_typeMap[\$fhirTypeName])) {
            return self::\$_typeMap[\$fhirTypeName];
        }
        return null;
    }

    /**
     * @param string \$className
     * @return string|null
     */
    public static function getTypeForClass(\$className)
    {
        if (null !== (\$idx = array_search(\$className, self::\$_typeMap))) {
            return \$idx;
        }
        return null;
    }

PHP;

    /**
     * @param \DCarbone\PHPFHIR\Config\VersionConfig $config
     * @param \DCarbone\PHPFHIR\Definition $definition
     * @return string
     */
    public static function build(VersionConfig $config, Definition $definition)
    {
        $rootNamespace = $config->getNamespace();

        $typeMap = [];
        foreach ($definition->getTypes()->getIterator() as $type) {
            $typeMap[$type->getFHIRName()] = $type->getFullyQualifiedClassName(true);
        }

        $out = FileUtils::buildFileHeader($config->getNamespace());

        $out .= <<<PHP
/**
 * @class PHPFHIRTypeMap
PHP;

        if ('' !== $rootNamespace) {
            $out .= "\n * @package {$rootNamespace}\n";
        }

        $out .= <<<PHP
 */
class PHPFHIRTypeMap
{

PHP;

        $out .= sprintf(self::VARS, var_export($typeMap, true));
        $out .= "\n";
        $out .= self::FUNCS;
        return $out . '}';
    }
}