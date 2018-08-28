<?php

namespace DCarbone\PHPFHIR\Utilities;

use DCarbone\PHPFHIR\ClassGenerator\Template\ClassTemplate;
use DCarbone\PHPFHIR\ClassGenerator\Template\Method\BaseMethodTemplate;
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
     * @var null|array \$data
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
     * @param ClassTemplate $class
     * @param BaseMethodTemplate $method
     */
    public static function implementResourceContainer(Config $config,
                                                      ClassTemplate $class,
                                                      BaseMethodTemplate $method)
    {
        $method->addBlockToBody(<<<PHP
if (is_object(\$data)) {
    \$this->{sprintf('set%s', substr(strrchr(get_class(\$data), 'FHIR'), 4))}(\$data);
} else if (is_array(\$data)) {
    if (1 === (\$cnt = count(\$data))) {
        \$this->{sprintf('set%s', key(\$data))}(reset(\$data));
    } else if (1 < \$cnt) {
        throw new \InvalidArgumentException(sprintf('ResourceContainers may only contain 1 object, "%d" values provided', \$cnt));
    }
} else if (null !== \$data) {
    throw new \\InvalidArgumentException('\$data expected to be object or array, saw '.gettype(\$data));
}
PHP
        );
    }
}