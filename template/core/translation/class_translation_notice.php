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

final class <?php echo $coreFile; ?> implements \JsonSerializable
{
    public function __construct(
        private readonly string $path,
        private readonly string $kind,
        private readonly string $sourceVersion,
        private readonly string $targetVersion,
        private readonly string $message,
    ) {
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getSourceVersion(): string
    {
        return $this->sourceVersion;
    }

    public function getTargetVersion(): string
    {
        return $this->targetVersion;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function jsonSerialize(): array
    {
        return [
            'path' => $this->path,
            'kind' => $this->kind,
            'sourceVersion' => $this->sourceVersion,
            'targetVersion' => $this->targetVersion,
            'message' => $this->message,
        ];
    }
}
<?php return ob_get_clean();
