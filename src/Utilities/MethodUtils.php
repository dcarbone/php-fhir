<?php

namespace DCarbone\PHPFHIR\Utilities;

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Definition\Type;
use DCarbone\PHPFHIR\Definition\Type\Property;

/**
 * Class SetterUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class MethodUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function createPrimitiveSetter(Config $config, Type $type, Property $property)
    {
        $propName = $property->getName();
        $varName = NameUtils::getPropertyVariableName($propName);
        $phpType = $property->getPHPTypeName();
        $propType = $property->getValueType();
        $methodName = ($property->isCollection() ? 'add' : 'set') . NameUtils::getPropertyMethodName($propName);

        $out = "    /**\n";
        $out .= $property->getDocBlockDocumentationFragment();
        $out .= "     * @param null|{$phpType}\n";
        $out .= "     * @return \$this\n";
        $out .= "     */\n";
        $out .= "    public function ";
        $out .= $methodName;
        $out .= "({$varName})\n    {\n";
        $out .= <<<PHP
        if (null === {$varName}) {
            return \$this; 
        }
        if (is_scalar({$varName})) {
            {$varName} = new {$propType->getClassName()}({$varName});
        }
        if (!({$varName} instanceof {$propType->getClassName()})) {
            throw new \InvalidArgumentException(sprintf(
                '{$type->getClassName()}::{$methodName} - Argument 1 expected to be instance of {$propType->getFullyQualifiedClassName(true)} or appropriate scalar value, %s seen.',
                gettype({$varName})
            ));
        }

PHP;
        $out .= "        \$this->{$propName}";
        if ($property->isCollection()) {
            $out .= '[]';
        }
        $out .= " = {$varName};\n";
        $out .= "        return \$this;\n    }\n";

        return $out;
    }

    /**
     * TODO: Implement value type, pattern, and/or allowable value check(s)
     *
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function createPrimitiveTypeValueSetter(Config $config, Type $type, Property $property)
    {
        $propName = $property->getName();
        $varName = NameUtils::getPropertyVariableName($propName);
        $phpType = $property->getPHPTypeName();
        $methodName = ($property->isCollection() ? 'add' : 'set') . NameUtils::getPropertyMethodName($propName);
        return <<<PHP
    /**
{$property->getDocBlockDocumentationFragment()}
     * @param null|{$phpType}
     * @return \$this
     */
    public function {$methodName} ($varName)
    {
        \$this->{$propName} = {$varName};
        return \$this;
    }

PHP;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function createDefaultSetter(Config $config, Type $type, Property $property)
    {
        $propName = $property->getName();
        $propType = $property->getValueType();
        $varName = NameUtils::getPropertyVariableName($propName);
        $phpType = $property->getPHPTypeName();
        $methodName = ($property->isCollection() ? 'add' : 'set') . NameUtils::getPropertyMethodName($propName);

        $out = "    /**\n";
        $out .= $property->getDocBlockDocumentationFragment();
        $out .= "     * @param null|{$phpType}\n";
        $out .= "     * @return \$this\n";
        $out .= "     */\n";
        $out .= "    public function ";
        $out .= $methodName;
        $out .= "({$propType->getClassName()} {$varName} = null)\n    {\n";
        $out .= <<<PHP
        if (null === {$varName}) {
            return \$this; 
        }
        \$this->{$propName}
PHP;
        if ($property->isCollection()) {
            $out .= '[]';
        }
        $out .= " = {$varName};\n";
        $out .= "        return \$this;\n    }\n";

        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type\Property $property
     * @return string
     */
    public static function createGetter(Config $config, Property $property) {
        $methodName = 'get'.NameUtils::getPropertyMethodName($property->getName());
        $collection = $property->isCollection() ? '[]' : '';
        return <<<PHP
    /**
     * @return null|{$property->getPHPTypeName()}{$collection}
     */
    public function {$methodName}()
    {
        return \$this->{$property->getName()};
    }

PHP;

    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildToString(Config $config, Type $type)
    {
        $out = <<<PHP
    /**
     * @return string
     */
    public function __toString()
    {
        return
PHP;

        if ($type->isPrimitive() || $type->isPrimitiveContainer()) {
            $out .= " (string)\$this->getValue();";
        } else {
            $out .= " (string)\$this->_fhirElementName;";
        }

        return $out . "\n    }\n";
    }
}