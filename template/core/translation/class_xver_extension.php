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

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

/**
 * An official HL7 cross-version extension that carries one source-version field in the target version.
 *
 * A simple extension holds a single value, whose type is one of $valueTypes.  A complex extension has $children, one
 * per nested extension, each naming the source field it carries.
 */
final class <?php echo $coreFile; ?>

{
    /**
     * @param string[] $valueTypes FHIR type names the value may take in the target version, empty if complex.
     * @param self[] $children
     * @param null|string $datatype Source datatype missing from the target version, emitted as a "_datatype" child.
     */
    public function __construct(
        private readonly string $url,
        private readonly string $field,
        private readonly bool $modifier,
        private readonly array $valueTypes,
        private readonly array $children,
        private readonly null|string $datatype,
    ) {
    }

    /** For a nested extension this is the name the parent extension uses to identify it. */
    public function getUrl(): string
    {
        return $this->url;
    }

    /** Name of the source type's field the extension carries. */
    public function getField(): string
    {
        return $this->field;
    }

    public function isModifier(): bool
    {
        return $this->modifier;
    }

    /** @return string[] */
    public function getValueTypes(): array
    {
        return $this->valueTypes;
    }

    /** @return self[] */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function isComplex(): bool
    {
        return [] !== $this->children;
    }

    public function getDatatype(): null|string
    {
        return $this->datatype;
    }
}
<?php return ob_get_clean();
