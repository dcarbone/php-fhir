<?php

/*
 * Copyright 2018-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Enum\TypeKindEnum $typeKind */
/** @var null|\DCarbone\PHPFHIR\Definition\Type $parentType */
/** @var string $typeClassName */

$directProperties = $type->getProperties()->getDirectIterator();

ob_start(); ?>
    /**
     * @param \SimpleXMLElement|string|null $sxe
     * @param null|<?php echo $type->getFullyQualifiedClassName(true); ?> $type
     * @param null|int $libxmlOpts
     * @return null|<?php echo $type->getFullyQualifiedClassName(true); ?>

     */
    public static function xmlUnserialize($sxe = null, <?php echo PHPFHIR_INTERFACE_TYPE; ?> $type = null, $libxmlOpts = <?php echo  null === ($opts = $config->getLibxmlOpts()) ? 'null' : $opts; ?>)
    {
        if (null === $sxe) {
            return null;
        }
        if (is_string($sxe)) {
            libxml_use_internal_errors(true);
            $sxe = new \SimpleXMLElement($sxe, $libxmlOpts, false);
            if ($sxe === false) {
                throw new \DomainException(sprintf('<?php echo $typeClassName; ?>::xmlUnserialize - String provided is not parseable as XML: %s', implode(', ', array_map(function(\libXMLError $err) { return $err->message; }, libxml_get_errors()))));
            }
            libxml_use_internal_errors(false);
        }
        if (!($sxe instanceof \SimpleXMLElement)) {
            throw new \InvalidArgumentException(sprintf('<?php echo $typeClassName?>::xmlUnserialize - $sxe value must be null, \\SimpleXMLElement, or valid XML string, %s seen', gettype($sxe)));
        }
        if (null === $type) {
            $type = new <?php echo $typeClassName; ?>;
        } elseif (!is_object($type) || !($type instanceof <?php echo $typeClassName; ?>)) {
            throw new \RuntimeException(sprintf(
                '<?php echo $typeClassName; ?>::xmlUnserialize - $type must be instance of <?php echo $type->getFullyQualifiedClassName(true); ?> or null, %s seen.',
                is_object($type) ? get_class($type) : gettype($type)
            ));
        }
<?php if (null !== $parentType) : ?>
        <?php echo $parentType->getClassName(); ?>::xmlUnserialize($sxe, $type);
<?php endif; ?>
        $xmlNamespaces = $sxe->getDocNamespaces(false, false);
        if ([] !== $xmlNamespaces) {
            $ns = reset($xmlNamespaces);
            if (false !== $ns && '' !== $ns) {
                $type->_xmlns = $ns;
            }
        }
<?php if (0 < count($directProperties)) : ?>
        $attributes = $sxe->attributes();
        $children = $sxe->children();
<?php endif;
return ob_get_clean();