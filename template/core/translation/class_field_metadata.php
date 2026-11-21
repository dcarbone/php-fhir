<?php declare(strict_types=1);

/*
 * Copyright 2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$typeInterface = $config->getCoreFiles()->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

final class <?php echo $coreFile; ?>

{
    /** @param class-string<<?php echo $typeInterface->getFullyQualifiedName(true); ?>> $type */
    public function __construct(
        private readonly string $type,
        private readonly bool $collection,
        private readonly string $getter,
        private readonly string $setter,
    ) {
    }

    /** @return class-string<<?php echo $typeInterface->getFullyQualifiedName(true); ?>> */
    public function getType(): string
    {
        return $this->type;
    }

    public function isCollection(): bool
    {
        return $this->collection;
    }

    public function getGetter(): string
    {
        return $this->getter;
    }

    public function getSetter(): string
    {
        return $this->setter;
    }
}
<?php return ob_get_clean();
