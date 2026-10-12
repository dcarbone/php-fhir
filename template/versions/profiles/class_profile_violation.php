<?php declare(strict_types=1);

/*
 * Copyright 2018-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var string $copyright */
/** @var string $namespace */
/** @var string $className */

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $namespace; ?>;

<?php echo $copyright; ?>


/**
 * A single way in which a resource fails to conform to a profile.
 */
final class <?php echo $className; ?> implements \JsonSerializable, \Stringable
{
    public const RULE_MIN = 'min';
    public const RULE_MAX = 'max';
    public const RULE_TYPE = 'type';
    public const RULE_FIXED = 'fixed';
    public const RULE_PATTERN = 'pattern';
    public const RULE_BINDING = 'binding';
    public const RULE_SLICE_MIN = 'slice-min';
    public const RULE_SLICE_MAX = 'slice-max';

    public function __construct(
        private readonly string $path,
        private readonly string $rule,
        private readonly string $message,
    ) {
    }

    /** @return string Location in the resource, e.g. "name[0].family" */
    public function getPath(): string
    {
        return $this->path;
    }

    /** @return string One of the RULE_* constants. */
    public function getRule(): string
    {
        return $this->rule;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function jsonSerialize(): array
    {
        return ['path' => $this->path, 'rule' => $this->rule, 'message' => $this->message];
    }

    public function __toString(): string
    {
        return sprintf('%s: %s', $this->path, $this->message);
    }
}
<?php return ob_get_clean();
