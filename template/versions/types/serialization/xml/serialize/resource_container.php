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

use DCarbone\PHPFHIR\Utilities\NameUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$config = $version->getConfig();
$namespace = $version->getFullyQualifiedName(false);
$localProperties = $type->getLocalProperties()->getLocalPropertiesIterator();

ob_start(); ?>
    /**
     * @param null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_XML_WRITER); ?> $xw
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_XML_WRITER); ?>

     */
    public function xmlSerialize(null|<?php echo PHPFHIR_CLASSNAME_XML_WRITER; ?> $xw = null): <?php echo PHPFHIR_CLASSNAME_XML_WRITER; ?>

    {
        if (null === $xw) {
            $xw = new <?php echo PHPFHIR_CLASSNAME_XML_WRITER; ?>();
        }
        if (!$xw->isOpen()) {
            $xw->openMemory();
        }
        if (!$xw->isDocStarted()) {
            $docStarted = true;
            $xw->startDocument();
        }
<?php foreach($localProperties as $property) : ?>
        if (null !== ($v = $this-><?php echo $property->getGetterName(); ?>())) {
            return $v->xmlSerialize($xw);
        }
<?php endforeach; ?>
        if (!$xw->isRootOpen()) {
            $openedRoot = true;
            $xw->openRootNode($config, '<?php echo NameUtils::getTypeXMLElementName($type); ?>', $this->_getSourceXmlns());
        }
<?php
return ob_get_clean();