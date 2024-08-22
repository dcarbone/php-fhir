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

use DCarbone\PHPFHIR\Enum\TypeKind;
use DCarbone\PHPFHIR\Utilities\DocumentationUtils;
use DCarbone\PHPFHIR\Utilities\TypeHintUtils;

/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Property[] $properties */

$isPrimitiveType = $type->getKind()->isOneOf(TypeKind::PRIMITIVE, TypeKind::LIST);

ob_start();
foreach ($properties as $property) :
    if ($property->isOverloaded()) {
        continue;
    }

    $documentation = DocumentationUtils::compilePropertyDocumentation($property, 5, true);

    $propertyName = $property->getName();
    $isCollection = $property->isCollection();
    $propertyType = $property->getValueFHIRType();
    $propertyTypeClassName = $propertyType->getClassName();
    $propertyTypeKind = $propertyType->getKind();
?>

    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @return <?php echo TypeHintUtils::propertyGetterTypeDoc($config, $property, true); ?>

     */
    public function get<?php echo ucfirst($propertyName); ?>(): <?php echo TypeHintUtils::propertyTypeHint($config, $property, true); ?>

    {
        return $this-><?php echo $propertyName; ?>;
    }
<?php if ($propertyType->hasPrimitiveParent() || $propertyTypeKind->isOneOf(TypeKind::PRIMITIVE_CONTAINER, TypeKind::PRIMITIVE, TypeKind::LIST)) : ?>

    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param <?php echo TypeHintUtils::propertySetterTypeDoc($config, $property, false); ?> $<?php echo $propertyName; ?>

     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_ENUM_XML_LOCATION_ENUM); ?> $xmlLocation
     * @return static
     */
    public function <?php echo $property->getSetterName(); ?>(<?php echo TypeHintUtils::propertySetterTypeHint($config, $property, true); ?> $<?php echo $property; ?> = null, <?php echo PHPFHIR_ENUM_XML_LOCATION_ENUM; ?> $xmlLocation = <?php echo PHPFHIR_ENUM_XML_LOCATION_ENUM; ?>::ATTRIBUTE): self
    {
        if (null !== $<?php echo $propertyName; ?> && !($<?php echo $propertyName; ?> instanceof <?php echo $propertyTypeClassName; ?>)) {
            $<?php echo $propertyName; ?> = new <?php echo $propertyTypeClassName; ?>($<?php echo $propertyName; ?>);
        }
        $this->_trackValue<?php if ($isCollection) : ?>Added(<?php else : ?>Set($this-><?php echo $propertyName; ?>, $<?php echo $propertyName; endif; ?>);
        if (!isset($this->_primitiveXmlLocations[self::<?php echo $property->getFieldConstantName(); ?>])) {
            $this->_primitiveXmlLocations[self::<?php echo $property->getFieldConstantName(); ?>] = [];
        }
        $this->_primitiveXmlLocations[self::<?php echo $property->getFieldConstantName(); ?>]<?php if ($isCollection) : ?>[]<?php else : ?>[0]<?php endif; ?> = $xmlLocation;
        $this-><?php echo $propertyName; ?><?php echo $isCollection ? '[]' : ''; ?> = $<?php echo $propertyName; ?>;
        return $this;
    }
<?php   if ($isCollection) : ?>

    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param <?php echo $propertyType->getFullyQualifiedClassName(true);?>[] $<?php echo $propertyName; ?>

     * @param <?php echo $config->getFullyQualifiedName(true, PHPFHIR_ENUM_XML_LOCATION_ENUM); ?> $xmlLocation
     * @return static
     */
    public function set<?php echo ucfirst($propertyName); ?>(array $<?php echo $propertyName; ?> = [], <?php echo PHPFHIR_ENUM_XML_LOCATION_ENUM; ?> $xmlLocation = <?php echo PHPFHIR_ENUM_XML_LOCATION_ENUM; ?>::ATTRIBUTE): self
    {
        unset($this->_primitiveXmlLocations[self::<?php echo $property->getFieldConstantName(); ?>]);
        if ([] !== $this-><?php echo $propertyName; ?>) {
            $this->_trackValuesRemoved(count($this-><?php echo $propertyName; ?>));
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
    elseif ($propertyTypeKind->isContainer($config->getVersion()->getName())) : ?>

    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param null|<?php echo $config->getFullyQualifiedName(true) . '\\' . PHPFHIR_INTERFACE_CONTAINED_TYPE; ?> $<?php echo $propertyName; ?>

     * @return static
     */
    public function <?php echo $property->getSetterName() ?>(null|<?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?> $<?php echo $propertyName; ?> = null): self
    {
        <?php if ($isCollection) : ?>$this->_trackValueAdded(<?php else : ?>$this->_trackValueSet($this-><?php echo $propertyName; ?>, $<?php echo $propertyName; endif; ?>);
        $this-><?php echo $propertyName; ?><?php echo $isCollection ? '[]' : ''; ?> = $<?php echo $propertyName; ?>;
        return $this;
    }
<?php   if ($isCollection) : ?>

    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param <?php echo $config->getFullyQualifiedName(true) . '\\' . PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>[] $<?php echo $propertyName; ?>

     * @return static
     */
    public function set<?php echo ucfirst($propertyName); ?>(array $<?php echo $propertyName; ?> = []): self
    {
        if ([] !== $this-><?php echo $propertyName; ?>) {
            $this->_trackValuesRemoved(count($this-><?php echo $propertyName; ?>));
            $this-><?php echo $propertyName; ?> = [];
        }
        if ([] === $<?php echo $propertyName; ?>) {
            return $this;
        }
        foreach($<?php echo $propertyName; ?> as $v) {
            if (is_object($v)) {
                if ($v instanceof <?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>) {
                    $this-><?php echo $property->getSetterName(); ?>($v);
                } else {
                    throw new \InvalidArgumentException(sprintf(
                        '<?php echo $type->getClassName(); ?> - Field "<?php echo $propertyName; ?>" must be an array of objects implementing <?php echo PHPFHIR_INTERFACE_CONTAINED_TYPE; ?>, object of type %s seen',
                        get_class($v)
                    ));
                }
            } elseif (is_array($v)) {
                $typeClass = <?php echo PHPFHIR_CLASSNAME_TYPEMAP ?>::getContainedTypeFromArray($v);
                if (null === $typeClass) {
                    throw new \InvalidArgumentException(sprintf(
                        '<?php echo $type->getClassName(); ?> - Unable to determine class for field "<?php echo $propertyName; ?>" from value: %s',
                        json_encode($v)
                    ));
                }
                $this-><?php echo $property->getSetterName(); ?>(new $typeClass($v));
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

     * @param <?php echo TypeHintUtils::propertySetterTypeDoc($config, $property, false); ?> $<?php echo $propertyName; ?>

     * @return static
     */
    public function <?php echo $property->getSetterName(); ?>(<?php echo TypeHintUtils::typeSetterTypeHint($config, $propertyType, true); ?> $<?php echo $propertyName; ?> = null): self
    {
        if (null === $<?php echo $propertyName; ?>) {
            $<?php echo $propertyName; ?> = new <?php echo $propertyTypeClassName; ?>();
        }
        $this->_trackValue<?php if ($isCollection) : ?>Added(<?php else : ?>Set($this-><?php echo $propertyName; ?>, $<?php echo $propertyName; endif; ?>);
        $this-><?php echo $propertyName; ?><?php echo $isCollection ? '[]' : ''; ?> = $<?php echo $propertyName; ?>;
        return $this;
    }
<?php   if ($isCollection) : ?>

    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param <?php echo $property->getValueFHIRType()->getFullyQualifiedClassName(true); ?>[] $<?php echo $propertyName; ?>

     * @return static
     */
    public function set<?php echo ucfirst($propertyName); ?>(array $<?php echo $propertyName; ?> = []): self
    {
        if ([] !== $this-><?php echo $propertyName; ?>) {
            $this->_trackValuesRemoved(count($this-><?php echo $propertyName; ?>));
            $this-><?php echo $propertyName; ?> = [];
        }
        if ([] === $<?php echo $propertyName; ?>) {
            return $this;
        }
        foreach($<?php echo $propertyName; ?> as $v) {
            if (is_object($v)) {
                if ($v instanceof <?php echo $property->getValueFHIRType()->getClassName(); ?>) {
                    $this-><?php echo $property->getSetterName(); ?>($v);
                } else {
                    throw new \InvalidArgumentException(sprintf(
                        '<?php echo $type->getClassName(); ?> - Field "<?php echo $propertyName; ?>" must be an array of objects implementing <?php echo $property->getValueFHIRType()->getClassName(); ?>, object of type %s seen',
                        get_class($v)
                    ));
                }
            } else {
                throw new \InvalidArgumentException(sprintf(
                    '<?php echo $type->getClassName(); ?> - Unable to set value for field "<?php echo $propertyName; ?>" from value: %s',
                    json_encode($v)
                ));
            }
        }
        return $this;
    }
<?php   endif;
    endif;
endforeach;

return ob_get_clean();