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
/** @var array<string, string> $classMap profile url => fully qualified class name, without leading slash */
/** @var array<string, string> $baseTypeMap profile url => FHIR type name */
/** @var string $interface fully qualified name, without leading slash, of the package map interface */

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $namespace; ?>;

use <?php echo $interface; ?>;

<?php echo $copyright; ?>


/**
 * Index of the generated profile classes of a single package, keyed by canonical profile url.
 */
abstract class <?php echo $className; ?> implements <?php echo substr($interface, (int)strrpos($interface, '\\') + 1); ?>

{
    /** @var array<string, class-string> */
    private const _PROFILES = [
<?php foreach ($classMap as $url => $fqcn) : ?>
        <?php echo var_export($url, true); ?> => <?php echo var_export($fqcn, true); ?>,
<?php endforeach; ?>
    ];

    /** @var array<string, string> */
    private const _BASE_TYPES = [
<?php foreach ($baseTypeMap as $url => $type) : ?>
        <?php echo var_export($url, true); ?> => <?php echo var_export($type, true); ?>,
<?php endforeach; ?>
    ];

    /**
     * @return array<string, class-string>
     */
    public static function getProfileClasses(): array
    {
        return self::_PROFILES;
    }

    /**
     * @param string $url Canonical profile url, without a "|version" suffix.
     * @return null|class-string
     */
    public static function getProfileClassname(string $url): null|string
    {
        return self::_PROFILES[$url] ?? null;
    }

    /**
     * @param string $url Canonical profile url, without a "|version" suffix.
     * @return null|string FHIR name of the type the profile constrains.
     */
    public static function getProfileBaseType(string $url): null|string
    {
        return self::_BASE_TYPES[$url] ?? null;
    }
}
<?php return ob_get_clean();
