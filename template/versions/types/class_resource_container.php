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
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

use DCarbone\PHPFHIR\Utilities\NameUtils;

$config = $version->getConfig();
$coreFiles = $config->getCoreFiles();

$xmlWriterClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_XML_WRITER);
$serializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);
$unserializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG);

$versionCoreFiles = $version->getCoreFiles();

$versionContainedTypeInterface = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_INTERFACE_VERSION_CONTAINED_TYPE);
$versionTypeMapClass = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP);

ob_start();

// build file header
echo require_with(
    PHPFHIR_TEMPLATE_VERSION_TYPES_DIR . '/header.php',
    [
        'version' => $version,
        'type' => $type,
    ]
); ?>

    /** @var null|<?php echo $versionContainedTypeInterface->getFullyQualifiedName(true); ?> */
    private null|<?php echo $versionContainedTypeInterface->getEntityName(); ?> $containedType = null;

    public function __construct(null|<?php echo $versionContainedTypeInterface->getEntityName(); ?> $containedType = null,
                                null|iterable $fhirComments = null)
    {
        if (null !== $containedType) {
            $this->setContainedType($containedType);
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
     * TODO: empty, pending validation system overhaul
     */
    public function _getValidationRules(): array
    {
        return [];
    }

    /**
     * TODO: empty, pending validation system overhaul
     */
    public function _getValidationErrors(): array
    {
        return [];
    }

    /**
     * @return null|<?php echo $versionContainedTypeInterface->getFullyQualifiedName(true); ?>

     */
    public function getContainedType(): null|<?php echo $versionContainedTypeInterface->getEntityName(); ?>

    {
        return $this->containedType ?? null;
    }

    /**
     * @param null|<?php echo $versionContainedTypeInterface->getFullyQualifiedName(true); ?> $containedType
     * @return static
     */
    public function setContainedType(null|<?php echo $versionContainedTypeInterface->getEntityName(); ?> $containedType): self
    {
        if (null === $containedType) {
            unset($this->containedType);
            return $this;
        }
        $this->containedType = $containedType;
        return $this;
    }

<?php
echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR . '/xml/unserialize/header.php',
    [
        'version' => $version,
        'type' => $type,
    ]
);
?>
        foreach ($element->children() as $child) {
            /** @var <?php echo $versionContainedTypeInterface->getFullyQualifiedName(true); ?> $class */
            $class = <?php echo $versionTypeMapClass->getEntityName(); ?>::getContainedTypeClassNameFromXML($child);
            $type->setContainedType($class::xmlUnserialize($child, null, $config));
            break;
        }
        return $type;
    }

    /**
     * @param null|<?php echo $xmlWriterClass->getFullyQualifiedName(true); ?> $xw
     * @param null|<?php echo $serializeConfigClass->getFullyQualifiedName(true); ?> $config
     * @return <?php echo $xmlWriterClass->getFullyQualifiedName(true); ?>

     */
    public function xmlSerialize(null|<?php echo $xmlWriterClass->getEntityName(); ?> $xw = null,
                                 null|<?php echo $serializeConfigClass->getEntityName(); ?> $config = null): <?php echo $xmlWriterClass->getEntityName(); ?>;

    {
        $containedType = $this->getContainedType();
        if (null !== $containedType) {
            return $containedType->xmlSerialize($xw, $config);
        }
        if (null === $xw) {
            $xw = new <?php echo $xmlWriterClass->getEntityName(); ?>();
        }
        if (!$xw->isOpen()) {
            $xw->openMemory();
        }
        if (!$xw->isDocStarted()) {
            $docStarted = true;
            $xw->startDocument();
        }
        if (null === $config) {
            $config = (new <?php echo PHPFHIR_VERSION_CLASSNAME_VERSION; ?>())->getConfig()->getSerializeConfig();
        }
        if (!$xw->isRootOpen()) {
            $rootOpened = true;
            $xw->openRootNode($config, '<?php echo NameUtils::getTypeXMLElementName($type); ?>', $this->_getSourceXMLNS());
        }
        if (isset($rootOpened) && $rootOpened) {
            $xw->endElement();
        }
        if (isset($docStarted) && $docStarted) {
            $xw->endDocument();
        }
        return $xw;
    }

<?php
// unserialize portion
echo require_with(
        PHPFHIR_TEMPLATE_VERSION_TYPES_SERIALIZATION_DIR . '/json/unserialize/header.php',
    [
        'version' => $version,
        'type' => $type,
    ]
);
?>
    }

    public function __toString(): string
    {
        return self::FHIR_TYPE_NAME;
    }

    /**
     * @return null|object
     */
    public function jsonSerialize(): mixed
    {
        return $this->getContainedType();
    }
}
<?php
return ob_get_clean();
