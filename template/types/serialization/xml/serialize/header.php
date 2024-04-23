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
     * @param null|\DOMElement $element
     * @param null|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_CLASSNAME_CONFIG; ?> $config
     * @return \DOMElement<?php if ($typeKind !== TypeKind::PRIMITIVE) : ?>

     * @throws \DOMException<?php endif; ?>

     */
    public function xmlSerialize(\DOMElement $element = null, null|<?php echo PHPFHIR_CLASSNAME_CONFIG ?> $config = null): \DOMElement
    {
        if (null === $element) {
            $dom = new \DOMDocument();
            $dom->loadXML($this->_getFHIRXMLElementDefinition('<?php echo $xmlName; ?>'), $config?->getLibxmlOpts() ?? 0);
            $element = $dom->documentElement;
        }
<?php if ($type->hasParentWithLocalProperties()) : ?>
        parent::xmlSerialize($element);
<?php endif;
return ob_get_clean();