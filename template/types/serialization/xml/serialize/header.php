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

$namespace = $config->getFullyQualifiedName(false);
$typeKind = $type->getKind();
$xmlName = NameUtils::getTypeXMLElementName($type);

ob_start(); ?>
    /**
     * @param null|\SimpleXMLElement $element
     * @param null|int|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_CLASSNAME_CONFIG; ?> $config PHP FHIR config.  Supports an integer value interpreted as libxml opts for backwards compatibility.
     * @return \SimpleXMLElement<?php if ($typeKind !== TypeKind::PRIMITIVE || $type->hasParent()) : ?>

     * @throws \Exception<?php endif; ?>

     */
    public function xmlSerialize(\SimpleXMLElement $element = null, null|int|<?php echo PHPFHIR_CLASSNAME_CONFIG ?> $config = null): \SimpleXMLElement
    {
        if (is_int($config)) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>(['libxmlOpts' => $config]);
        } else if (null === $config) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>();
        }
        if (null === $element) {
            $element = new \SimpleXMLElement($this->_getFhirXmlElementDefinition('<?php echo NameUtils::getTypeXMLElementName($type); ?>'), $config->getLibxmlOpts());
        }
<?php if ($type->hasParentWithLocalProperties()) : ?>
        parent::xmlSerialize($element, $config);
<?php endif;
return ob_get_clean();