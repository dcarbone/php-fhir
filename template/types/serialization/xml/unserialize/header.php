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

$namespace = $config->getNamespace(false);
$versionName = $config->getVersion()->getName();

ob_start(); ?>
    /**
     * @param null|string|\DOMNode $element
     * @param null|<?php echo $type->getFullyQualifiedClassName(true); ?> $type
     * @param null|int|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?> $config XML serialization config.  Supports an integer value interpreted as libxml opts for backwards compatibility.
     * @return null|<?php echo $type->getFullyQualifiedClassName(true); ?>

     */
    public static function xmlUnserialize(null|string|\DOMNode $element, null|<?php echo PHPFHIR_INTERFACE_XML_SERIALIZABLE; ?> $type = null, null|int|<?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG ?> $config = null): null|self
    {
        if (null === $element) {
            return null;
        }
        if (is_int($config)) {
            $libxmlOpts = $config;
            $config = null;
        } else {
            $libxmlOpts = $config?->getLibxmlOpts() ?? <?php echo PHPFHIR_INTERFACE_XML_SERIALIZALE_CONFIG; ?>::DEFAULT_LIBXML_OPTS;
        }
        if (is_string($element)) {
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            if (false === $dom->loadXML($element, $libxmlOpts)) {
                throw new \DomainException(sprintf(
                    '%s::xmlUnserialize - String provided is not parseable as XML: %s',
                    ltrim(substr(__CLASS__, (int)strrpos(__CLASS__, '\\')), '\\'),
                    implode(', ', array_map(function(\libXMLError $err) { return $err->message; }, libxml_get_errors()))
                ));
            }
            libxml_use_internal_errors(false);
            $element = $dom->documentElement;
        }
<?php if ($type->isAbstract()) : // abstract types may not be instantiated directly ?>
        if (null === $type) {
            throw new \RuntimeException(sprintf('%s::xmlUnserialize: Cannot unserialize directly into root type', static::class));
        }<?php else : ?>
        if (null === $type) {
            $type = new static(null);
        }<?php endif; ?> else if (!($type instanceof <?php echo $typeClassName; ?>)) {
            throw new \RuntimeException(sprintf(
                '%s::xmlUnserialize - $type must be instance of \\%s or null, %s seen.',
                ltrim(substr(__CLASS__, (int)strrpos(__CLASS__, '\\')), '\\'),
                static::class,
                get_class($type)
            ));
        }
        if ('' === $type->_getFHIRXMLNamespace() && '' !== ($ens = (string)$element->namespaceURI)) {
            $type->_setFHIRXMLNamespace($ens);
        }
<?php return ob_get_clean();