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
$imports = $coreFile->getimports();

$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$serializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);
$unserializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG);
$xmlWriterClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_XML_WRITER);
$resourceIDInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_RESOURCE_ID_TYPE);

$imports->addCoreFileImports(
    $typeInterface,
    $serializeConfigClass,
    $unserializeConfigClass,
    $xmlWriterClass,
    $resourceIDInterface,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

interface <?php echo $coreFile; ?> extends <?php echo $typeInterface; ?>

{
    /**
     * Must return the root XMLNS value found in the source.  Null indicates no "xmlns" was found.  Only defined when
     * unserializing XML, and only used when serializing XML.
     *
     * @return null|string
     */
    public function _getSourceXMLNS(): null|string;

    /**
     * Must return the ID-containing element of this resource, if defined.
     *
     * @return null|<?php echo $resourceIDInterface->getFullyQualifiedName(true); ?>

     */
    public function getId(): null|<?php echo $resourceIDInterface; ?>;

    /**
     * @param string|\SimpleXMLElement $element
     * @param null|<?php echo $unserializeConfigClass->getFullyQualifiedName(true); ?> $config
     * @param null|<?php echo $coreFile->getFullyQualifiedName(true); ?> $type Instance of this class to unserialize into.  If left null, a new instance will be created.
     * @return static
     */
    public static function xmlUnserialize(string|\SimpleXMLElement $element,
                                          null|<?php echo $unserializeConfigClass ?> $config = null,
                                          null|<?php echo $coreFile; ?> $type = null): self;

    /**
     * @param null|<?php echo $xmlWriterClass->getFullyQualifiedName(true); ?> $xw
     * @param null|<?php echo $serializeConfigClass->getFullyQualifiedName(true); ?> $config
     * @return <?php echo $xmlWriterClass->getFullyQualifiedName(true); ?>

     */
    public function xmlSerialize(null|<?php echo $xmlWriterClass; ?> $xw = null,
                                 null|<?php echo $serializeConfigClass; ?> $config = null): <?php echo $xmlWriterClass; ?>;

    /**
     * @param string|\stdClass $decoded Encoded or decoded JSON.
     * @param null|<?php echo $unserializeConfigClass->getFullyQualifiedName(true); ?> $config
     * @param null|<?php echo $coreFile->getFullyQualifiedName(true); ?> $type Instance of this class to unserialize into.  If left null, a new instance will be created.
     * @return static
     */
    public static function jsonUnserialize(string|\stdClass $decoded,
                                           null|<?php echo $unserializeConfigClass; ?> $config = null,
                                           null|<?php echo $coreFile; ?> $type = null): self;
}
<?php return ob_get_clean();
