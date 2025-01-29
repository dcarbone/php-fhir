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

$config = $version->getConfig();

$coreFiles = $config->getCoreFiles();
$constantsClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_CLASSNAME_CONSTANTS);
$unserializeConfigClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_ENCODING_CLASSNAME_UNSERIALIZE_CONFIG);

if ($type->isResourceType() || $type->hasResourceTypeParent()) {
    $typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_RESOURCE_TYPE);
} else {
    $typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_ELEMENT_TYPE);
}

$versionCoreFiles = $version->getCoreFiles();
$versionClass = $versionCoreFiles->getCoreFileByEntityName(PHPFHIR_VERSION_CLASSNAME_VERSION);

ob_start(); ?>
    /**
     * @param <?php if ($type->isResourceType() || $type->hasResourceTypeParent()) : ?>string|\stdClass|<?php endif; ?>array $json
     * @param <?php if ($type->isResourceType() || $type->hasResourceTypeParent()) : ?>null|<?php endif; echo $unserializeConfigClass->getFullyQualifiedName(true); ?> $config
     * @param null|<?php echo $type->getFullyQualifiedClassName(true); ?> $type
     * @return <?php echo $type->getFullyQualifiedClassName(true); ?>

     * @throws \Exception
     */
    public static function jsonUnserialize(<?php if ($type->isResourceType() || $type->hasResourceTypeParent()) : ?>string|\stdClass|<?php endif; ?>array $json,
                                           <?php if ($type->isResourceType() || $type->hasResourceTypeParent()) : ?>null|<?php endif; echo $unserializeConfigClass->getEntityName() ?> $config<?php if ($type->isResourceType() || $type->hasResourceTypeParent()) : ?> = null<?php endif;?>,
                                           null|<?php echo $typeInterface->getEntityName(); ?> $type = null): self
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
        if (null === $config) {
            $config = (new <?php echo $versionClass->getEntityName(); ?>())->getConfig()->getUnserializeConfig();
        }
        if (is_string($json)) {
            $json = json_decode(json: $json, associative: true, depth: $config->getJSONDecodeMaxDepth());
        } else if (is_object($json)) {
            $json = (array)$json;
        }
<?php if ($type->hasConcreteParent()) : ?>
        parent::jsonUnserialize($json, $type, $config);<?php elseif (!$type->hasCommentContainerParent() && $type->isCommentContainer()) : ?>
        if (isset($data[<?php echo $constantsClass->getEntityName(); ?>::JSON_FIELD_FHIR_COMMENTS])) {
            $type->_setFHIRComments((array)$data[<?php echo $constantsClass->getEntityName(); ?>::JSON_FIELD_FHIR_COMMENTS]);
        }
<?php endif;

return ob_get_clean();
