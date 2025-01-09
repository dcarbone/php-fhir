<?php declare(strict_types=1);

/*
 * Copyright 2018-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

$versionContainedTypeInterface = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE);

$isPrimitiveType = $type->getKind()->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST);

ob_start();
foreach ($type->getProperties()->getIndexedIterator() as $i => $property) :
    if ($property->getOverloadedProperty()) {
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

     * @return <?php echo TypeHintUtils::propertyGetterDocHint($version, $property, true); ?>

     */
    public function get<?php echo ucfirst($propertyName); ?>(): <?php echo TypeHintUtils::propertyDeclarationHint($version, $property, true); ?>

    {
        return $this-><?php echo $propertyName; ?> ?? <?php if ($property->isCollection()) : ?>[]<?php else : ?>null<?php endif; ?>;
    }
<?php if ($isCollection) : ?>

    /**
     * @return \ArrayIterator<<?php echo $propertyType->getFullyQualifiedClassName(true); ?>>
     */
    public function get<?php echo ucfirst($propertyName); ?>Iterator(): iterable
    {
        if (!isset($this-><?php echo $propertyName; ?>) || [] === $this-><?php echo $propertyName; ?>) {
            return new \EmptyIterator();
        }
        return new \ArrayIterator($this-><?php echo $propertyName; ?>);
    }
<?php
    endif;
     ?>

    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param <?php echo TypeHintUtils::buildSetterParameterDocHint($version, $property, !$property->isCollection(), true); ?> $<?php echo $propertyName; ?>

     * @return static
     */
    public function <?php echo $property->getSetterName(); ?>(<?php echo TypeHintUtils::buildSetterParameterHint($version, $property, !$property->isCollection(), true); ?> $<?php echo $property; ?>): self
    {
<?php if (!$property->isCollection()) : ?>
        if (null === $<?php echo $propertyName; ?>) {
            unset($this-><?php echo $propertyName; ?>);
            return $this;
        }
<?php endif;
if ($propertyTypeKind->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST, TypeKindEnum::PRIMITIVE_CONTAINER)) : ?>
        if (!($<?php echo $propertyName; ?> instanceof <?php echo $propertyTypeClassName; ?>)) {
            $<?php echo $propertyName; ?> = new <?php echo $propertyTypeClassName; ?>(value: $<?php echo $propertyName; ?>);
        }
<?php endif;
    if ($property->isCollection()) : ?>
        if (!isset($this-><?php echo $propertyName; ?>)) {
            $this-><?php echo $propertyName; ?> = [];
        }
<?php endif; ?>
        $this-><?php echo $propertyName; echo $isCollection ? '[]' : ''; ?> = $<?php echo $propertyName; ?>;
        return $this;
    }
<?php   if ($isCollection) : ?>

    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param <?php echo TypeHintUtils::buildSetterParameterDocHint($version, $property, false, true);?> ...$<?php echo $propertyName; ?>

     * @return static
     */
    public function set<?php echo ucfirst($propertyName); ?>(<?php echo TypeHintUtils::buildSetterParameterHint($version, $property, false, true); ?> ...$<?php echo $propertyName; ?>): self
    {
<?php if ($propertyType->isValueContainer()) : ?>
        $this-><?php echo $propertyName; ?> = [];
        foreach($<?php echo $propertyName; ?> as $v) {
            if ($v instanceof <?php echo $propertyTypeClassName; ?>) {
                $this-><?php echo $propertyName; ?>[] = $v;
            } else {
                $this-><?php echo $propertyName; ?>[] = new <?php echo $propertyTypeClassName; ?>(value: $v);
            }
        }
<?php else : ?>
        $this-><?php echo $propertyName; ?> = $<?php echo $propertyName; ?>;
<?php endif; ?>
        return $this;
    }
<?php   endif;
endforeach;

return ob_get_clean();