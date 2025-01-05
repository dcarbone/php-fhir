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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\DocumentationUtils;
use DCarbone\PHPFHIR\Utilities\TypeHintUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$config = $version->getConfig();
$coreFiles = $config->getCoreFiles();

$xmlLocationEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_XML_LOCATION);

$versionCoreFiles = $version->getCoreFiles();

$versionContainedTypeInterface = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_INTERFACE_VERSION_CONTAINED_TYPE);

$isPrimitiveType = $type->getKind()->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST);

ob_start();
foreach ($type->getLocalProperties()->getIndexedLocalPropertiesIterator() as $i => $property) :
    if ($property->isOverloaded()) {
        continue;
    }

    $documentation = DocumentationUtils::compilePropertyDocumentation($property, 5, true);

    $propertyName = $property->getName();
    $isCollection = $property->isCollection();
    $propertyType = $property->getValueFHIRType();
    $propertyTypeClassName = $propertyType->getClassName();
    $propertyTypeKind = $propertyType->getKind();

    if ($i > 0) {
        echo "\n";
    }
?>
    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @return <?php echo TypeHintUtils::propertyGetterTypeDoc($version, $property, true); ?>

     */
    public function get<?php echo ucfirst($propertyName); ?>(): <?php echo TypeHintUtils::propertyTypeHint($version, $property, true); ?>

    {
        return $this-><?php echo $propertyName; ?>;
    }<?php if ($isCollection) : ?>


    /**
     * @return \ArrayIterator<<?php echo $propertyType->getFullyQualifiedClassName(true); ?>>
     */
    public function get<?php echo ucfirst($propertyName); ?>Iterator(): iterable
    {
        if (null === $this-><?php echo $propertyName; ?> || [] === $this-><?php echo $propertyName; ?>) {
            return new \EmptyIterator();
        }
        return new \ArrayIterator($this-><?php echo $propertyName; ?>);
    }

    /**
     * @return \Generator<<?php echo $propertyType->getFullyQualifiedClassName(true); ?>>
     */
    public function get<?php echo ucfirst($propertyName); ?>Generator(): \Generator
    {
        foreach ((array)$this-><?php echo $propertyName; ?> as $v) {
            yield $v;
        }
    }
<?php
    endif;
    if ($propertyType->hasPrimitiveParent() || $propertyTypeKind->isOneOf(TypeKindEnum::PRIMITIVE_CONTAINER, TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST)) : ?>


    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param <?php echo TypeHintUtils::propertySetterTypeDoc($version, $property, false); ?> $<?php echo $propertyName; ?>

     * @param <?php echo $xmlLocationEnum->getFullyQualifiedName(true); ?> $xmlLocation
     * @return static
     */
    public function <?php echo $property->getSetterName(); ?>(<?php echo TypeHintUtils::propertySetterTypeHint($version, $property, true); ?> $<?php echo $property; ?> = null, <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?> $xmlLocation = <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?>::<?php if ($propertyType->isValueContainer()): ?>ELEMENT<?php else : ?>ATTRIBUTE<?php endif; ?>): self
    {
        if (null !== $<?php echo $propertyName; ?> && !($<?php echo $propertyName; ?> instanceof <?php echo $propertyTypeClassName; ?>)) {
            $<?php echo $propertyName; ?> = new <?php echo $propertyTypeClassName; ?>($<?php echo $propertyName; ?>);
        }
        if (!isset($this->_xmlLocations[self::<?php echo $property->getFieldConstantName(); ?>])) {
            $this->_xmlLocations[self::<?php echo $property->getFieldConstantName(); ?>] = [];
        }
        <?php if ($isCollection) : ?>if ([] === $this->_xmlLocations[self::<?php echo $property->getFieldConstantName(); ?>]) {
            $this->_xmlLocations[self::<?php echo $property->getFieldConstantName(); ?>][0] = $xmlLocation;
        } else {
            $this->_xmlLocations[self::<?php echo $property->getFieldConstantName(); ?>][] = <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?>::ELEMENT;
        }<?php else : ?>
$this->_xmlLocations[self::<?php echo $property->getFieldConstantName(); ?>][0] = $xmlLocation;<?php endif; ?>

        $this-><?php echo $propertyName; ?><?php echo $isCollection ? '[]' : ''; ?> = $<?php echo $propertyName; ?>;
        return $this;
    }
<?php   if ($isCollection) : ?>


    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param <?php echo $propertyType->getFullyQualifiedClassName(true);?>[] $<?php echo $propertyName; ?>

     * @param <?php echo $xmlLocationEnum->getFullyQualifiedName(true); ?> $xmlLocation
     * @return static
     */
    public function set<?php echo ucfirst($propertyName); ?>(array $<?php echo $propertyName; ?> = [], <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?> $xmlLocation = <?php echo PHPFHIR_ENCODING_ENUM_XML_LOCATION; ?>::<?php if ($propertyType->isValueContainer()): ?>ELEMENT<?php else : ?>ATTRIBUTE<?php endif; ?>): self
    {
        unset($this->_xmlLocations[self::<?php echo $property->getFieldConstantName(); ?>]);
        if ([] !== $this-><?php echo $propertyName; ?>) {
            $this-><?php echo $propertyName; ?> = [];
        }
        if ([] === $<?php echo $propertyName; ?>) {
            return $this;
        }
        foreach($<?php echo $propertyName; ?> as $v) {
            if ($v instanceof <?php echo $propertyTypeClassName; ?>) {
                $this-><?php echo $property->getSetterName(); ?>($v, $xmlLocation);
            } else {
                $this-><?php echo $property->getSetterName(); ?>(new <?php echo $propertyTypeClassName; ?>($v), $xmlLocation);
            }
        }
        return $this;
    }
<?php   endif;
    elseif ($propertyTypeKind->isContainer($version)) : ?>


    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param null|<?php echo $version->getFullyQualifiedName(true) . '\\' . PHPFHIR_INTERFACE_VERSION_CONTAINED_TYPE; ?> $<?php echo $propertyName; ?>

     * @return static
     */
    public function <?php echo $property->getSetterName() ?>(null|<?php echo PHPFHIR_INTERFACE_VERSION_CONTAINED_TYPE; ?> $<?php echo $propertyName; ?> = null): self
    {
        $this-><?php echo $propertyName; ?><?php echo $isCollection ? '[]' : ''; ?> = $<?php echo $propertyName; ?>;
        return $this;
    }
<?php   if ($isCollection) : ?>


    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param <?php echo $versionContainedTypeInterface->getFullyQualifiedName(true); ?>[] $<?php echo $propertyName; ?>

     * @return static
     */
    public function set<?php echo ucfirst($propertyName); ?>(array $<?php echo $propertyName; ?> = []): self
    {
        if ([] !== $this-><?php echo $propertyName; ?>) {
            $this-><?php echo $propertyName; ?> = [];
        }
        if ([] === $<?php echo $propertyName; ?>) {
            return $this;
        }
        foreach($<?php echo $propertyName; ?> as $v) {
            if (is_object($v)) {
                if ($v instanceof <?php echo PHPFHIR_INTERFACE_VERSION_CONTAINED_TYPE; ?>) {
                    $this-><?php echo $property->getSetterName(); ?>($v);
                } else {
                    throw new \InvalidArgumentException(sprintf(
                        '<?php echo $type->getClassName(); ?> - Field "<?php echo $propertyName; ?>" must be an array of objects implementing <?php echo $version->getFullyQualifiedName(true, PHPFHIR_INTERFACE_VERSION_CONTAINED_TYPE); ?>, object of type %s seen',
                        get_class($v)
                    ));
                }
            } elseif (is_array($v)) {
                $typeClassName = <?php echo PHPFHIR_CLASSNAME_VERSION_TYPE_MAP; ?>::getContainedTypeClassNameFromArray($v);
                unset($v[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE]);
                $this-><?php echo $property->getSetterName(); ?>(new $typeClassName($v));
            } else {
                throw new \InvalidArgumentException(sprintf(
                    '<?php echo $type->getClassName(); ?> - Unable to determine class for field "<?php echo $propertyName; ?>" from value: %s',
                    json_encode($v)
                ));
            }
        }
        return $this;
    }
<?php   endif;
    else : ?>


    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param <?php echo TypeHintUtils::propertySetterTypeDoc($version, $property, false); ?> $<?php echo $propertyName; ?>

     * @return static
     */
    public function <?php echo $property->getSetterName(); ?>(<?php echo TypeHintUtils::typeSetterTypeHint($version, $propertyType, true); ?> $<?php echo $propertyName; ?> = null): self
    {
        if (null === $<?php echo $propertyName; ?>) {
            $<?php echo $propertyName; ?> = new <?php echo $propertyTypeClassName; ?>();
        }
        $this-><?php echo $propertyName; ?><?php echo $isCollection ? '[]' : ''; ?> = $<?php echo $propertyName; ?>;
        return $this;
    }
<?php   if ($isCollection) : ?>


    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param <?php echo $property->getValueFHIRType()->getFullyQualifiedClassName(true); ?> ...$<?php echo $propertyName; ?>

     * @return static
     */
    public function set<?php echo ucfirst($propertyName); ?>(<?php echo $property->getValueFHIRType()->getClassName(); ?> ...$<?php echo $propertyName; ?>): self
    {
        if ([] !== $this-><?php echo $propertyName; ?>) {
            $this-><?php echo $propertyName; ?> = [];
        }
        if ([] === $<?php echo $propertyName; ?>) {
            return $this;
        }
        foreach($<?php echo $propertyName; ?> as $v) {
            $this-><?php echo $property->getSetterName(); ?>($v);
        }
        return $this;
    }
<?php   endif;
    endif;
endforeach;

return ob_get_clean();