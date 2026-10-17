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
 * Contract of the index of generated profile classes that every profile package has.
 */
interface <?php echo $className; ?>

{
    /**
     * @return array<string, class-string>
     */
    public static function getProfileClasses(): array;

    /**
     * @param string $url Canonical profile url, without a "|version" suffix.
     * @return null|class-string
     */
    public static function getProfileClassname(string $url): null|string;

    /**
     * @param string $url Canonical profile url, without a "|version" suffix.
     * @return null|string FHIR name of the type the profile constrains.
     */
    public static function getProfileBaseType(string $url): null|string;
}
<?php return ob_get_clean();
