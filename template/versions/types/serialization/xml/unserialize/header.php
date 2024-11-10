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

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Enum\TypeKind $typeKind */
/** @var null|\DCarbone\PHPFHIR\Version\Definition\Type $parentType */
/** @var string $typeClassName */

$namespace = $version->getFullyQualifiedName(false);
$versionName = $version->getName();

ob_start(); ?>
    /**
     * @param null|string|\SimpleXMLElement $element
     * @param null|<?php echo $type->getFullyQualifiedClassName(true); ?> $type
     * @param null|int|\<?php echo ('' === $namespace ? '' : "{$namespace}\\") . PHPFHIR_CLASSNAME_CONFIG; ?> $config PHP FHIR config.  Supports an integer value interpreted as libxml opts for backwards compatibility.
     * @return null|<?php echo $type->getFullyQualifiedClassName(true); ?>

     */
    public static function xmlUnserialize(null|string|\SimpleXMLElement $element, null|<?php echo PHPFHIR_INTERFACE_TYPE; ?> $type = null, null|int|<?php echo PHPFHIR_CLASSNAME_CONFIG ?> $config = null): null|self
    {
        if (null === $element) {
            return null;
        }
        if (is_int($config)) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>([<?php echo PHPFHIR_ENUM_CONFIG_KEY; ?>::LIBXML_OPTS->value => $config]);
        } else if (null === $config) {
            $config = new <?php echo PHPFHIR_CLASSNAME_CONFIG; ?>();
        }
        if (is_string($element)) {
            $element = new \SimpleXMLElement($element, $config->getLibxmlOpts());
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
        if (null !== ($ns = $element->getNamespaces()[''] ?? null)) {
            $type->_setSourceXmlns((string)$ns);
        }
<?php return ob_get_clean();