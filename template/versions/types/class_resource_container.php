<?php declare(strict_types=1);

/*
 * Copyright 2024-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

use DCarbone\PHPFHIR\Utilities\NameUtils;

$config = $version->getConfig();
$coreFiles = $config->getCoreFiles();
$versionCoreFiles = $version->getCoreFiles();
$imports = $type->getImports();

$resourceContainerInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_RESOURCE_CONTAINER_TYPE);
$containedInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_CONTAINED_TYPE);
$commentContainerTrait = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_TRAIT_COMMENT_CONTAINER);

$versionContainedTypeInterface = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE);
$versionTypeMapClass = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP);

ob_start();

?>
class <?php echo $type->getClassName(); ?> implements <?php echo $resourceContainerInterface; ?>

{
    use <?php echo $commentContainerTrait; ?>;

    /** @var null|<?php echo $versionContainedTypeInterface->getFullyQualifiedName(true); ?> */
    private null|<?php echo $versionContainedTypeInterface; ?> $contained = null;

    public function __construct(null|<?php echo $versionContainedTypeInterface; ?> $contained = null,
                                null|iterable $fhirComments = null)
    {
        if (null !== $contained) {
            $this->setContained($contained);
        }
        if (null !== $fhirComments) {
            $this->_setFHIRComments($fhirComments);
        }
    }

    public function _getFHIRTypeName(): string
    {
        return self::FHIR_TYPE_NAME;
    }

    /**
     * @return null|<?php echo $versionContainedTypeInterface->getFullyQualifiedName(true); ?>

     */
    public function getContainedType(): null|<?php echo $containedInterface; ?>

    {
        return $this->contained ?? null;
    }

    /**
     * @param null|<?php echo $versionContainedTypeInterface->getFullyQualifiedName(true); ?> $contained
     * @return static
     */
    public function setContainedType(null|<?php echo $containedInterface; ?> $contained): self
    {
        if (null === $contained) {
            unset($this->contained);
            return $this;
        }
        if (!($contained instanceof <?php echo $versionContainedTypeInterface; ?>)) {
            throw new \InvalidArgumentException(sprintf(
                'Contained type must implement "%s", provided type "%s" does not.',
                <?php echo $versionContainedTypeInterface; ?>::class,
                $contained::class,
            ));
        }
        $this->contained = $contained;
        return $this;
    }

    public function __toString(): string
    {
        return (string)($this->contained ?? self::FHIR_TYPE_NAME);
    }

    /**
     * @return null|object
     */
    public function jsonSerialize(): mixed
    {
        return $this->contained ?? null;
    }
}
<?php
return ob_get_clean();
