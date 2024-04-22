<?php declare(strict_types=1);

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */

use DCarbone\PHPFHIR\Enum\TypeKind;
use DCarbone\PHPFHIR\Utilities\CopyrightUtils;

$namespace = $config->getNamespace(false);

$containerType = $types->getContainerType($config->getVersion()->getName());
if (null === $containerType) {
    throw new \RuntimeException(sprintf(
        'Unable to locate either "%s" or "%s" type',
        TypeKind::RESOURCE_CONTAINER->value,
        TypeKind::RESOURCE_INLINE->value
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
 * Interface <?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>
 *
 * This interface is applied to any class that is containable within a <?php $containerType->getFullyQualifiedClassName(true); ?><?php if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
interface <?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?> extends <?php echo PHPFHIR_INTERFACE_TYPE; ?>, <?php echo PHPFHIR_INTERFACE_XML_SERIALIZABLE ?>

{
    /**
     * The return from this method is used only when json serializing this type
     * @return string
     */
    public function _getResourceType(): string;
}
<?php return ob_get_clean();