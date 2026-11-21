<?php declare(strict_types=1);

/*
 * Copyright 2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();
$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$fieldMetadataClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_FIELD_METADATA);
$factoryInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_INTERFACE_FIELD_METADATA_FACTORY);
$imports = $coreFile->getImports();
$imports->addCoreFileImports($typeInterface, $fieldMetadataClass, $factoryInterface);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

final class <?php echo $coreFile; ?> implements FieldMetadataFactoryInterface
{
    /** @var array<class-string<TypeInterface>, array<string, <?php echo $fieldMetadataClass->getFullyQualifiedName(true); ?>>> */
    private array $_cache = [];

    /**
     * @return array<string, <?php echo $fieldMetadataClass->getFullyQualifiedName(true); ?>>
     */
    public function forType(TypeInterface $type): array
    {
        $className = $type::class;
        if (isset($this->_cache[$className])) {
            return $this->_cache[$className];
        }

        $fields = [];
        $reflection = new \ReflectionClass($className);
        foreach ($reflection->getProperties() as $property) {
            if ($property->isStatic()) {
                continue;
            }

            foreach ($property->getAttributes() as $attribute) {
                $fieldType = $attribute->getName();
                if (!is_a($fieldType, TypeInterface::class, true)) {
                    continue;
                }

                $fieldName = $property->getName();
                $propertyType = $property->getType();
                $fields[$fieldName] = new FieldMetadata(
                    type: $fieldType,
                    collection: $propertyType instanceof \ReflectionNamedType
                        && 'array' === $propertyType->getName(),
                    getter: 'get' . ucfirst($fieldName),
                    setter: 'set' . ucfirst($fieldName),
                );
                break;
            }
        }

        return $this->_cache[$className] = $fields;
    }
}
<?php return ob_get_clean();
