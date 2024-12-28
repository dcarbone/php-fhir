<?php

/*
 * Copyright 2018-2020 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

$directProperties = $type->getProperties()->getDirectIterator();

ob_start(); ?>
    /**
     * @param null|\DOMElement $element
     * @param null|int $libxmlOpts
     * @return string|\DOMElement
     */
    public function xmlSerialize(\DOMElement $element = null, $libxmlOpts = <?php echo  null === ($opts = $config->getLibxmlOpts()) ? 'null' : $opts; ?>)
    {
<?php foreach($directProperties as $property) : ?>
        if (null !== ($v = $this->get<?php echo ucfirst($property->getName()); ?>())) {
            return $v->xmlSerialize($element, $libxmlOpts);
        }
<?php endforeach; ?>
        if (null === $element) {
            $dom = new \DOMDocument();
            $dom->loadXML($this->_getFHIRXMLElementDefinition(), $libxmlOpts);
            $element = $dom->documentElement;
        }
<?php
return ob_get_clean();