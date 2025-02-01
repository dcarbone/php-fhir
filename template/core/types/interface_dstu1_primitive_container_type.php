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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();

$valueContainerInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_VALUE_CONTAINER_TYPE);
$serializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);
$xmlWriterClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_XML_WRITER);
$xmlValueLocationEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION);

$imports = $coreFile->getimports();

$imports->addCoreFileImports(
    $valueContainerInterface,
    $serializeConfigClass,
    $xmlWriterClass,
    $xmlValueLocationEnum,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

interface <?php echo $coreFile->getEntityName(); ?> extends <?php echo $valueContainerInterface->getEntityName(); ?>

{
    /**
     * Must return true if this primitive container type has a field set other than "value".  This is used during
     * serialization.
     *
     * @return bool
     */
    public function _nonValueFieldDefined(): bool;

    /**
     * @param null|<?php echo $xmlWriterClass->getFullyQualifiedName(true); ?> $xw
     * @param null|<?php echo $serializeConfigClass->getFullyQualifiedName(true); ?> $config
     * @param null|<?php echo $xmlValueLocationEnum->getFullyQualifiedName(true); ?> $valueLocation
     * @return <?php echo $xmlWriterClass->getFullyQualifiedName(true); ?>

     */
    public function xmlSerialize(null|<?php echo $xmlWriterClass->getEntityName(); ?> $xw,
                                 null|<?php echo $serializeConfigClass->getEntityName(); ?> $config,
                                 null|<?php echo $xmlValueLocationEnum->getEntityName(); ?> $valueLocation = null): <?php echo $xmlWriterClass->getEntityName() ?>;
}
<?php return ob_get_clean();
