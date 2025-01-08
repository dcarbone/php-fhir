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
        return $this-><?php echo $propertyName; ?>;
    }
<?php if ($isCollection) : ?>

    /**
     * @return \ArrayIterator<<?php echo $propertyType->getFullyQualifiedClassName(true); ?>>
     */
    public function get<?php echo ucfirst($propertyName); ?>Iterator(): iterable
    {
        if ([] === $this-><?php echo $propertyName; ?>) {
            return new \EmptyIterator();
        }
        return new \ArrayIterator($this-><?php echo $propertyName; ?>);
    }

    /**
     * @return \Generator<<?php echo $propertyType->getFullyQualifiedClassName(true); ?>>
     */
    public function get<?php echo ucfirst($propertyName); ?>Generator(): \Generator
    {
        foreach ($this-><?php echo $propertyName; ?> as $v) {
            yield $v;
        }
    }
<?php
    endif;
     ?>

    /**<?php if ('' !== $documentation) : ?>

<?php echo $documentation; ?>
     *<?php endif; ?>

     * @param <?php echo TypeHintUtils::buildSetterParameterDocHint($version, $property, false, true); ?> $<?php echo $propertyName; ?>

     * @return static
     */
    public function <?php echo $property->getSetterName(); ?>(<?php echo TypeHintUtils::buildSetterParameterHint($version, $property, false, true); ?> $<?php echo $property; ?>): self
    {
<?php if ($propertyType->isValueContainer() || $propertyTypeKind->isOneOf(TypeKindEnum::PRIMITIVE, TypeKindEnum::LIST)) :
    ?>
        if (!($<?php echo $propertyName; ?> instanceof <?php echo $propertyTypeClassName; ?>)) {
            $<?php echo $propertyName; ?> = new <?php echo $propertyTypeClassName; ?>($<?php echo $propertyName; ?>);
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
        if ([] !== $this-><?php echo $propertyName; ?>) {
            $this-><?php echo $propertyName; ?> = [];
        }
        foreach($<?php echo $propertyName; ?> as $v) {
<?php if ($propertyType->isValueContainer()) : ?>            if ($v instanceof <?php echo $propertyTypeClassName; ?>) {
    <?php endif; ?>            $this-><?php echo $propertyName; echo $isCollection ? '[]' : ''; ?> = $v;
<?php if ($propertyType->isValueContainer()) : ?>            } else {
                $this-><?php echo $propertyName; echo $isCollection ? '[]' : ''; ?> = new <?php echo $propertyTypeClassName; ?>($v);
            }
<?php endif; ?>
        }
        return $this;
    }
<?php   endif;
endforeach;

return ob_get_clean();