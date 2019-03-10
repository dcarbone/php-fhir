<?php

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Enum\TypeKindEnum $typeKind */

ob_start(); ?>
    /**
     * @param \SimpleXMLElement|string|null \$sxe
     * @param null|<?php echo $type->getFullyQualifiedClassName(true); ?> $type
     * @return null|<?php echo $type->getFullyQualifiedClassName(true); ?>

     */
    public static function xmlUnserialize($sxe = null, $type = null)
    {
        if (null === $sxe) {
            return null;
        }
        if (is_string($sxe)) {
            libxml_use_internal_errors(true);
            $sxe = new \SimpleXMLElement($sxe);
            if ($sxe === false) {
                throw new \DomainException(sprintf('<?php echo $typeClassName; ?>::xmlUnserialize - String provided is not parseable as XML: %s', implode(', ', array_map(function(\libXMLError $err) { return $err->message; }, libxml_get_errors()))));
            }
            libxml_use_internal_errors(false);
        }
        if (!($sxe instanceof \SimpleXMLElement)) {
            throw new \InvalidArgumentException(sprintf('<?php echo $typeClassName?>::xmlUnserialize - $sxe value must be null, \\SimpleXMLElement, or valid XML string, %s seen', gettype($sxe)));
        }
        if (null === $type) {
<?php if (null !== $parentType): ?>
            $type = <?php echo $parentType->getClassName(); ?>::xmlUnserialize(\$sxe, new <?php echo $typeClassName; ?>);
<?php else : ?>
            $type = new <?php echo $typeClassName; ?>();
<?php endif; ?>
        } elseif (!is_object($type) || !($type instanceof <?php echo $typeClassName; ?>)) {
            throw new \RuntimeException(sprintf(
                '<?php echo $typeClassName; ?>::xmlUnserialize - $type must be instance of <?php echo $type->getFullyQualifiedClassName(true); ?> or null, %s seen.',
                is_object($type) ? get_class($type) : gettype($type)
            ));
        }
        $attributes = $sxe->attributes();
        $children = $sxe->children();
<?php return ob_get_clean();