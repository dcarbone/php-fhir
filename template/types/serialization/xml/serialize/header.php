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

use DCarbone\PHPFHIR\Enum\TypeKind;
use DCarbone\PHPFHIR\Utilities\NameUtils;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Type $parentType */

$namespace = $config->getNamespace(false);
$typeKind = $type->getKind();
$xmlName = NameUtils::getTypeXMLElementName($type);

ob_start(); ?>
    /**
     * @param null|\DOMNode $element
     * @param null|int|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?> $config XML serialization config.  Supports an integer value interpreted as libxml opts for backwards compatibility.
     * @return \DOMNode<?php if ($typeKind !== TypeKind::PRIMITIVE) : ?>

     * @throws \DOMException<?php endif; ?>

     */
    public function xmlSerialize(\DOMNode $element = null, null|int|<?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG ?> $config = null): \DOMNode
    {
        if (is_int($config)) {
            $libxmlOpts = $config;
            $config = null;
        } else {
            $libxmlOpts = $config?->getLibxmlOpts() ?? <?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?>::DEFAULT_LIBXML_OPTS;
        }
        if (null === $element) {
            $dom = new \DOMDocument();
            $dom->loadXML($this->_getFHIRXMLElementDefinition('<?php echo $xmlName; ?>'), $libxmlOpts);
            $element = $dom->documentElement;
        }
<?php if ($type->hasParentWithLocalProperties()) : ?>
        parent::xmlSerialize($element);
<?php endif;
return ob_get_clean();