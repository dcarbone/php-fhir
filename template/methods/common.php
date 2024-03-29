<?php declare(strict_types=1);

/*
 * Copyright 2018-2022 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Type|null $parentType */
/** @var null|bool $skipTypeName */
/** @var null|bool $skipGetXMLNamespace */
/** @var null|bool $skipGetXMLDefinition */

$xmlName = NameUtils::getTypeXMLElementName($type);
$skipTypeName = isset($skipTypeName) && $skipTypeName;
$skipGetXMLNamespace = isset($skipGetXMLNamespace) && $skipGetXMLNamespace;
$skipGetXMLDefinition = isset($skipGetXMLDefinition) && $skipGetXMLDefinition;

ob_start(); ?>
    /**
     * @return string
     */
    public function _getFHIRTypeName(): string
    {
        return self::FHIR_TYPE_NAME;
    }
<?php if (null === $parentType) : ?><?php if (!$skipGetXMLNamespace) : ?>

    /**
     * @return string
     */
    public function _getFHIRXMLNamespace(): string
    {
        return $this->_xmlns;
    }
<?php endif; ?>

<?php if (!$skipGetXMLDefinition) : ?>    /**
     * @param null|string $xmlNamespace
     * @return static
     */
    public function _setFHIRXMLNamespace(string $xmlNamespace): object
    {
        $this->_xmlns = trim((string)$xmlNamespace);
        return $this;
    }
<?php endif; ?>

<?php endif; ?>

    /**
     * @return string
     */
    public function _getFHIRXMLElementDefinition(): string
    {
        $xmlns = $this->_getFHIRXMLNamespace();
        if ('' !==  $xmlns) {
            $xmlns = " xmlns=\"{$xmlns}\"";
        }
        return "<<?php echo $xmlName; ?>{$xmlns}></<?php echo $xmlName; ?>>";
    }
<?php return ob_get_clean();