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
/** @var \DCarbone\PHPFHIR\Profiles\ProfileClass $profileClass */

$profile = $profileClass->getProfile();

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $profileClass->getNamespace(); ?>;

<?php echo $copyright; ?>


/**
 * Class <?php echo $profileClass->getClassName(); ?>

 *
 * Profile: <?php echo $profile->getUrl(); ?>

<?php if (null !== $profile->getTitle()) : ?> * Title: <?php echo str_replace('*/', '* /', $profile->getTitle()); ?>

<?php endif; ?>
 * Constrains: <?php echo $profile->getType(); ?>

 */
class <?php echo $profileClass->getClassName(); ?> extends <?php echo $profileClass->getExtendsClassName(); ?>

{
    public const PROFILE_URL = <?php echo var_export($profile->getUrl(), true); ?>;
    public const PROFILE_VERSION = <?php echo var_export($profile->getVersion(), true); ?>;
    public const PROFILE_NAME = <?php echo var_export($profile->getName(), true); ?>;
    public const PROFILE_BASE_TYPE = <?php echo var_export($profile->getType(), true); ?>;

    /**
     * @return string
     */
    public static function _getProfileUrl(): string
    {
        return self::PROFILE_URL;
    }
}
<?php return ob_get_clean();
