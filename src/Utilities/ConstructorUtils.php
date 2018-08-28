<?php

namespace DCarbone\PHPFHIR\Utilities;

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Definition\Type;

/**
 * Class ConstructorUtils
 * @package DCarbone\PHPFHIR\ClassGenerator\Utilities
 */
abstract class ConstructorUtils
{
    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildHeader(Config $config, Type $type)
    {
        $out = <<<PHP
    /**
     * {$type->getClassName()} Constructor

PHP;
        $out .= $type->getDocBlockDocumentationFragment();
        $out .= <<<PHP
     * @var mixed \$data Value depends upon object being constructed.
     */
    public function __construct(\$data = null)
    {

PHP;
        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildDefaultBody(Config $config, Type $type)
    {
        $properties = $type->getProperties();

        $out = '';
        if ($type->getParentType()) {
            $out .= "       parent::__construct(\$data);\n";
        }
        $out .= <<<PHP
        if (is_array(\$data)) {

PHP;
        foreach ($properties->getSortedIterator() as $property) {
            $name = $property->getName();
            if ($property->isCollection()) {
                $setter = 'add' . ucfirst($name);
            } else {
                $setter = 'set' . ucfirst($name);
            }
            $out .= <<<PHP
            if (isset(\$data['{$name}'])) {
                \$this->{$setter}(\$data['{$name}']);
            }

PHP;
        }
        $out .= <<<PHP
        } else if (null !== \$data) {
            throw new \InvalidArgumentException(
                '{$type->getFullyQualifiedClassName(true)}::__construct - Argument 1 expected to be array or null, '.
                gettype(\$data).
                ' seen.'
            );
        }

PHP;

        return $out;
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildPrimitiveBody(Config $config, Type $type)
    {
        $out = <<<PHP
        if (is_scalar(\$data)) {
            \$this->setValue(\$data);
            return;
        }

PHP;
        return $out . self::buildDefaultBody($config, $type);
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param \DCarbone\PHPFHIR\Definition\Type $type
     * @return string
     */
    public static function buildResourceContainerBody(Config $config, Type $type)
    {
        return <<<PHP
        if (is_array(\$data)) {
            \$key = key(\$data);
            if (!is_string(\$key)) {
                throw new \InvalidArgumentException(sprintf(
                    '{$type->getFullyQualifiedClassName(true)}::__construct - When \$data is an array, the first key must be a string with a value equal to one of the fields defined in this object.  %s seen',
                    \$key
                ));
            }
            \$this->{"set{\$key}"}(current(\$data));
        } else if (is_object(\$data)) {
            \$this->{sprintf('set%s', substr(strrchr(get_class(\$data), 'FHIR'), 4))}(\$data);
        } else if (null !== \$data) {
            throw new \InvalidArgumentException(sprintf(
                '{$type->getFullyQualifiedClassName(true)}::__construct - \$data must be an array, an object, or null.  %s seen.',
                gettype(\$data)
            ));
        }

PHP;
    }
}