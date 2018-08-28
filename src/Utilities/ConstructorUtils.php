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
    public static function implementDefault(Config $config, Type $type)
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
        if ($type->getParentType()) {
            $out .= "       parent::__construct(\$data);\n";
        }
        $out .= <<<PHP
        if (is_array(\$data)) {

PHP;
        foreach ($type->getProperties()->getSortedIterator() as $property) {
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
//        $method->addLineToBody('if (is_array($data)) {');
//        foreach ($class->getProperties() as $name => $property) {
//            if (0 === strpos($name, '_')) {
//                continue;
//            }
//            $method->addLineToBody('    if (isset($data[\'' . $name . '\'])) {');
//            if ($property->isCollection()) {
//                // TODO: case-insensitive method calling gives me...pains...
//                $method->addBlockToBody(<<<PHP
//        if (is_array(\$data['{$name}'])) {
//            foreach(\$data['{$name}'] as \$d) {
//                \$this->add{$name}(\$d);
//            }
//        } else {
//            throw new \\InvalidArgumentException('"{$name}" must be array of objects or null, '.gettype(\$data['{$name}']).' seen.');
//        }
//PHP
//                );
//            } else {
//                $method->addLineToBody('        $this->set' . ucfirst($name) . '($data[\'' . $name . '\']);');
//            }
//            $method->addLineToBody('    }');
//        }
//        $method->addBlockToBody(<<<PHP
//} else if (null !== \$data) {
//    throw new \\InvalidArgumentException('\$data expected to be array of values, saw "'.gettype(\$data).'"');
//}
//PHP
//        );
//        if ($class->getExtendedElementMapEntry()) {
//            $method->addLineToBody('parent::__construct($data);');
//        }
    }

    /**
     * @param \DCarbone\PHPFHIR\Config $config
     * @param ClassTemplate $class
     * @param BaseMethodTemplate $method
     */
    public static function implementPrimitive(Config $config,
                                              ClassTemplate $class,
                                              BaseMethodTemplate $method)
    {
        // TODO: type-specific checking?
        $method->addLineToBody('if (is_scalar($data)) {');
        $method->addLineToBody('    parent::__construct([\'value\' => $data]);;');
        $method->addLineToBody('} else {');
        $method->addLineToBody('    parent::__construct($data);');
        $method->addLineToBody('}');
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