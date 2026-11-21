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

final class <?php echo $coreFile; ?>

{
    /** @param array<string, null|string> $codeMappings source code => target code, or null if unmappable */
    public function __construct(
        private readonly string $sourceSystem,
        private readonly string $targetSystem,
        private readonly array $codeMappings,
        private readonly null|string $sourceVersion = null,
        private readonly null|string $targetVersion = null,
    ) {
        if ('' === $sourceVersion || '' === $targetVersion) {
            throw new \InvalidArgumentException('Code map versions must not be empty when specified.');
        }
    }

    public function getSourceSystem(): string
    {
        return $this->sourceSystem;
    }

    public function getTargetSystem(): string
    {
        return $this->targetSystem;
    }

    public function getSourceVersion(): null|string
    {
        return $this->sourceVersion;
    }

    public function getTargetVersion(): null|string
    {
        return $this->targetVersion;
    }

    public function hasSourceCode(string $code): bool
    {
        return array_key_exists($code, $this->codeMappings);
    }

    public function getTargetCode(string $sourceCode): null|string
    {
        return $this->codeMappings[$sourceCode] ?? null;
    }
}
<?php return ob_get_clean();
