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

use DCarbone\PHPFHIR\Utilities\NameUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$config = $version->getConfig();

$coreFiles = $config->getCoreFiles();
$xmlWriterClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_XML_WRITER);
$serializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_SERIALIZE_CONFIG);
$xmlLocationEnum = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_ENUM_VALUE_XML_LOCATION);

$versionCoreFiles = $version->getCoreFiles();
$versionClass = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION);

ob_start(); ?>
    /**
     * @param null|<?php echo $xmlWriterClass->getFullyQualifiedName(true); ?> $xw
     * @param null|<?php echo $serializeConfigClass->getFullyQualifiedName(true); ?> $config
<?php if ($type->isValueContainer() || $type->hasValueContainerParent()) : ?>
     * @param null|<?php echo $xmlLocationEnum->getFullyQualifiedName(true); ?> $valueLocation
<?php endif; ?>
     * @return <?php echo $xmlWriterClass->getFullyQualifiedName(true); ?>

     */
    public function xmlSerialize(null|<?php echo $xmlWriterClass->getEntityName(); ?> $xw = null,
                                 null|<?php echo $serializeConfigClass->getEntityName(); ?> $config = null<?php if ($type->isPrimitiveContainer() || $type->hasPrimitiveContainerParent()) : ?>,
                                 null|<?php echo $xmlLocationEnum->getEntityName(); ?> $valueLocation = null<?php endif; ?>): <?php echo $xmlWriterClass->getEntityName(); ?>

    {
        if (null === $config) {
            $config = (new <?php echo $versionClass->getEntityName(); ?>())->getConfig()->getSerializeConfig();
        }
        if (null === $xw) {
            $xw = new <?php echo $xmlWriterClass->getEntityName(); ?>($config);
        }
        if (!$xw->isOpen()) {
            $xw->openMemory();
        }
        if (!$xw->isDocStarted()) {
            $docStarted = true;
            $xw->startDocument();
        }
        if (!$xw->isRootOpen()) {
            $rootOpened = true;
            $xw->openRootNode('<?php echo NameUtils::getTypeXMLElementName($type); ?>', $this->_getSourceXMLNS());
        }
<?php return ob_get_clean();