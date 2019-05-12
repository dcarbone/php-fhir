<?php

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\CopyrightUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */

$namespace = $config->getNamespace(false);

$containerType = null;
foreach ($types->getIterator() as $type) {
    $kind = $type->getKind();
    if ($kind->isResourceContainer() || $kind->isInlineResource()) {
        $containerType = $type;
        break;
    }
}

$innerTypes = [];
foreach ($containerType->getProperties()->getSortedIterator() as $property) {
    if ($ptype = $property->getValueFHIRType()) {
        $innerTypes[] = $ptype;
    }
}

if (null === $containerType) {
    throw new \RuntimeException(sprintf(
        'Unable to locate either "%s" or "%s" type',
        TypeKindEnum::RESOURCE_CONTAINER,
        TypeKindEnum::RESOURCE_INLINE
    ));
}

ob_start();

echo "<?php\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo CopyrightUtils::getFullPHPFHIRCopyrightComment();

echo "\n\n";
?>
/**
 * Class PHPFHIRTypeMap<?php if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
abstract class PHPFHIRTypeMap
{
    /**
     * This array represents every type known to this lib
     * @var array
     */
    private static $_typeMap = [
<?php foreach ($types->getSortedIterator() as $type) : ?>
        '<?php echo $type->getFHIRName(); ?>' => '<?php echo $type->getFullyQualifiedClassName(true); ?>',
<?php endforeach; ?>    ];

    /**
     * Get fully qualified class name for FHIR Type name.  Returns null if type not found
     * @param string $typeName
     * @return string|null
     */
    public static function getTypeClass($typeName) {
        return (is_string($typeName) && isset(self::$_typeMap[$typeName])) ? self::$_typeMap[$typeName] : null;
    }

    /**
     * Returns the full internal class map
     * @return array
     */
    public static function getMap() {
        return self::$_typeMap;
    }
}
<?php return ob_get_clean();