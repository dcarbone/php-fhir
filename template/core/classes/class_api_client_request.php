<?php declare(strict_types=1);

/*
 * Copyright 2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\Version\Definition\Types $types */

$namespace = $config->getFullyQualifiedName(false);

ob_start();

echo "<?php declare(strict_types=1);\n\n";

if ('' !== $namespace) :
    echo "namespace {$namespace};\n\n";
endif;

echo $config->getBasePHPFHIRCopyrightComment();

echo "\n\n"; ?>
/**
 * This type is not intended to be used directly, and will have its API changed over time.
 *
 * Class <?php echo PHPFHIR_CLASSNAME_API_CLIENT_REQUEST; if ('' !== $namespace) : ?>

 * @package \<?php echo $namespace; ?>
<?php endif; ?>

 */
final class <?php echo PHPFHIR_CLASSNAME_API_CLIENT_REQUEST; ?>

{
    /** @var null|string */
    public null|string $host;

    /** @var int */
    public null|int $count = null;
    /** @var null|string */
    public null|string $since = null;
    /** @var null|string */
    public null|string $at = null;

    /** @var null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_ENUM_API_SORT); ?> */
    public null|<?php echo PHPFHIR_ENUM_API_SORT; ?> $sort = null;

    /** @var null|<?php echo $config->getFullyQualifiedName(true, PHPFHIR_ENUM_API_RESOURCE_LIST); ?> */
    public null|<?php echo PHPFHIR_ENUM_API_RESOURCE_LIST; ?> $resourceList = null;
}
<?php return ob_get_clean();