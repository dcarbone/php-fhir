<?php declare(strict_types=1);

/*
 * Copyright 2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

use DCarbone\PHPFHIR\Utilities\ImportUtils;

$coreFiles = $config->getCoreFiles();

$elementInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_ELEMENT_TYPE);
$serializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);
$xmlWriterClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_XML_WRITER);
$xmlValueLocationEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION);

$imports = $coreFile->getimports();

$imports->addCoreFileImports(
    $elementInterface,
    $serializeConfigClass,
    $xmlWriterClass,
    $xmlValueLocationEnum,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

interface <?php echo $coreFile->getEntityName(); ?> extends <?php echo $elementInterface->getEntityName(); ?>

{
    /**
     * Must return the appropriate "formatted" stringified version of this type's contained primitive type's value
     *
     * @return string
     */
    public function _getFormattedValue(): string;

    /**
     * Must return true if this primitive container type has a field set other than "value".  This is used during
     * serialization.
     *
     * @return bool
     */
    public function _nonValueFieldDefined(): bool;

    /**
     * @param <?php echo $xmlWriterClass->getFullyQualifiedName(true); ?> $xw
     * @param <?php echo $serializeConfigClass->getFullyQualifiedName(true); ?> $config
     * @param null|<?php echo $xmlValueLocationEnum->getFullyQualifiedName(true); ?> $valueLocation
     * @return <?php echo $xmlWriterClass->getFullyQualifiedName(true); ?>

     */
    public function xmlSerialize(<?php echo $xmlWriterClass->getEntityName(); ?> $xw,
                                 <?php echo $serializeConfigClass->getEntityName(); ?> $config,
                                 null|<?php echo $xmlValueLocationEnum->getEntityName(); ?> $valueLocation = null): <?php echo $xmlWriterClass->getEntityName(); ?>;
}

<?php return ob_get_clean();
