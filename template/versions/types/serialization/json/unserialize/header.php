<?php declare(strict_types=1);

/*
 * Copyright 2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

$sourceMeta = $version->getSourceMetadata();

$isResource = $type->isResourceType()
    || $type->hasResourceTypeParent()
    || $type->getKind()->isResourceContainer($version);

$config = $version->getConfig();

$coreFiles = $config->getCoreFiles();
$constantsClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_CONSTANTS);
$unserializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG);

if ($isResource) {
    $typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_RESOURCE_TYPE);
} else {
    $typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_ELEMENT_TYPE);
}

$versionCoreFiles = $version->getVersionCoreFiles();
$versionClass = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION);

ob_start(); ?>
    /**
     * @param <?php if ($isResource) : ?>string|<?php endif; ?>\stdClass $decoded
     * @param <?php if ($isResource) : ?>null|<?php endif; echo $unserializeConfigClass->getFullyQualifiedName(true); ?> $config
     * @param null|<?php echo $type->getFullyQualifiedClassName(true); ?> $type
     * @return <?php echo $type->getFullyQualifiedClassName(true); ?>

     * @throws \Exception
     */
    public static function jsonUnserialize(<?php if ($isResource) : ?>string|<?php endif; ?>\stdClass $decoded,
                                           <?php if ($isResource) : ?>null|<?php endif; echo $unserializeConfigClass ?> $config<?php if ($isResource) : ?> = null<?php endif;?>,
                                           null|<?php echo $typeInterface; ?> $type = null): self
    {
<?php if ($type->isAbstract()) : // abstract types may not be instantiated directly ?>
        if (null === $type) {
            throw new \RuntimeException(sprintf('%s::xmlUnserialize: Cannot unserialize directly into root type', static::class));
        }<?php else : ?>
        if (null === $type) {
            $type = new static();
        }<?php endif; ?> else if (!($type instanceof <?php echo $type->getClassName(); ?>)) {
            throw new \RuntimeException(sprintf(
                '%s::jsonUnserialize - $type must be instance of \\%s or null, %s seen.',
                ltrim(substr(__CLASS__, (int)strrpos(__CLASS__, '\\')), '\\'),
                static::class,
                get_class($type)
            ));
        }
<?php if ($isResource) : ?>
        if (null === $config) {
            $config = (new <?php echo $versionClass; ?>())->getConfig()->getUnserializeConfig();
        }
        if (is_string($decoded)) {
            $decoded = json_decode(json: $decoded,
                                associative: false,
                                depth: $config->getJSONDecodeMaxDepth(),
                                flags: $config->getJSONDecodeOpts());
        }
<?php endif;
if ($type->hasConcreteParent()) : ?>
        parent::jsonUnserialize($decoded, $config, $type); <?php
elseif (!$type->hasCommentContainerParent() && $type->isCommentContainer()) : ?>
        if (isset($decoded->{<?php echo $constantsClass; ?>::JSON_FIELD_FHIR_COMMENTS})) {
            $type->_setFHIRComments((array)$decoded->{<?php echo $constantsClass; ?>::JSON_FIELD_FHIR_COMMENTS});
        }<?php endif;

return ob_get_clean();
