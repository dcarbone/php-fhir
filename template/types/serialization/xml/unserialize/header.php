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

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Enum\TypeKind $typeKind */
/** @var null|\DCarbone\PHPFHIR\Definition\Type $parentType */
/** @var string $typeClassName */

$versionName = $config->getVersion()->getName();

ob_start(); ?>
    /**
     * @param null|string|\DOMElement $element
     * @param null|<?php echo $type->getFullyQualifiedClassName(true); ?> $type
     * @param null|int $libxmlOpts
     * @return null|<?php echo $type->getFullyQualifiedClassName(true); ?>

     */
    public static function xmlUnserialize(null|string|\DOMElement $element, null|<?php echo PHPFHIR_INTERFACE_XML_SERIALIZABLE; ?> $type = null, ?int $libxmlOpts = <?php echo  null === ($opts = $config->getLibxmlOpts()) ? 'null' : $opts; ?>): null|self
    {
        if (null === $element) {
            return null;
        }
        if (is_string($element)) {
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            if (false === $dom->loadXML($element, $libxmlOpts)) {
                throw new \DomainException(sprintf('<?php echo $typeClassName; ?>::xmlUnserialize - String provided is not parseable as XML: %s', implode(', ', array_map(function(\libXMLError $err) { return $err->message; }, libxml_get_errors()))));
            }
            libxml_use_internal_errors(false);
            $element = $dom->documentElement;
        }
<?php if ($type->isAbstract()) : // abstract types may not be instantiated directly ?>
        if (null === $type) {
            throw new \RuntimeException('<?php echo $typeClassName; ?>::xmlUnserialize: Cannot unserialize directly into root type');
        } else if (!($type instanceof <?php echo $typeClassName; ?>)) {
            throw new \RuntimeException(sprintf(
                '<?php echo $typeClassName; ?>::xmlUnserialize - $type must be child instance of <?php echo $type->getFullyQualifiedClassName(true); ?> or null, %s seen.',
                get_class($type)
            ));
        }
<?php else : ?>
        if (null === $type) {
            $type = new <?php echo $typeClassName; ?>(null);
        } else if (!($type instanceof <?php echo $typeClassName; ?>)) {
            throw new \RuntimeException(sprintf(
                '<?php echo $typeClassName; ?>::xmlUnserialize - $type must be instance of <?php echo $type->getFullyQualifiedClassName(true); ?> or null, %s seen.',
                get_class($type)
            ));
        }
<?php endif; ?>
        if ('' === $type->_getFHIRXMLNamespace() && (null === $element->parentNode || $element->namespaceURI !== $element->parentNode->namespaceURI)) {
            $type->_setFHIRXMLNamespace($element->namespaceURI);
        }
<?php return ob_get_clean();