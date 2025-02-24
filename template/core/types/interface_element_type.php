<?php declare(strict_types=1);

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\CoreFiles\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$serializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);
$unserializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG);
$xmlWriterClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_XML_WRITER);

$imports = $coreFile->getimports();

$imports->addCoreFileImports($typeInterface, $serializeConfigClass, $unserializeConfigClass, $xmlWriterClass);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

interface <?php echo $coreFile; ?> extends <?php echo $typeInterface; ?>

{
    /**
     * @param \SimpleXMLElement $element Decoded XML
     * @param <?php echo $unserializeConfigClass->getFullyQualifiedName(true); ?> $config
     * @param null|<?php echo $coreFile->getFullyQualifiedName(true); ?> $type Instance of this class to unserialize into.  If left null, a new instance will be created.
     * @return static
     */
    public static function xmlUnserialize(\SimpleXMLElement $element,
                                          <?php echo $unserializeConfigClass ?> $config,
                                          null|<?php echo $coreFile; ?> $type = null): self;

    /**
     * @param <?php echo $xmlWriterClass->getFullyQualifiedName(true); ?> $xw
     * @param <?php echo $serializeConfigClass->getFullyQualifiedName(true); ?> $config
     */
    public function xmlSerialize(<?php echo $xmlWriterClass; ?> $xw,
                                 <?php echo $serializeConfigClass; ?> $config): void;

    /**
     * @param \stdClass $json Decoded JSON
     * @param <?php echo $unserializeConfigClass->getFullyQualifiedName(true); ?> $config
     * @param null|<?php echo $coreFile->getFullyQualifiedName(true); ?> $type Instance of this class to unserialize into.  If left null, a new instance will be created.
     * @return static
     */
    public static function jsonUnserialize(\stdClass $json,
                                           <?php echo $unserializeConfigClass; ?> $config,
                                           null|<?php echo $coreFile; ?> $type = null): self;
}
<?php return ob_get_clean();
