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

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$namespace = $config->getFullyQualifiedName(false);
$localProperties = $type->getLocalProperties()->localPropertiesIterator();

ob_start(); ?>
    /**
     * @param null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_XML_WRITER); ?> $xw
     * @param null|int|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_CLASSNAME_CONFIG; ?> $config PHP FHIR config.  Supports an integer value interpreted as libxml opts for backwards compatibility.
     * @return <?php echo $config->getFullyQualifiedName(true, PHPFHIR_CLASSNAME_XML_WRITER); ?>

     */
    public function xmlSerialize(null|<?php echo PHPFHIR_CLASSNAME_XML_WRITER; ?> $xw = null, null|int|<?php echo PHPFHIR_CLASSNAME_CONFIG ?> $config = null): <?php echo PHPFHIR_CLASSNAME_XML_WRITER; ?>

    {
        if (is_int($config)) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>([<?php echo PHPFHIR_ENUM_CONFIG_KEY; ?>::LIBXML_OPTS->value => $config]);
        } else if (null === $config) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>();
        }
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
            return $v->xmlSerialize($xw, $config);
        }
<?php endforeach; ?>
        if (!$xw->isRootOpen()) {
            $openedRoot = true;
            $xw->openRootNode($config, '<?php echo NameUtils::getTypeXMLElementName($type); ?>', $this->_getSourceXmlns());
        }
<?php
return ob_get_clean();