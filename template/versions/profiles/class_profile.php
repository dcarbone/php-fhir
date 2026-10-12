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
$ns = $profileClass->getProfilesNamespace();
$validator = '\\' . $ns . '\\' . PHPFHIR_PROFILES_CLASSNAME_VALIDATOR;
$violation = '\\' . $ns . '\\' . PHPFHIR_PROFILES_CLASSNAME_VIOLATION;
$accessors = $profileClass->getSliceAccessors();
$slices = $profileClass->getSlices();

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
     * Cardinality, fixed / pattern value, choice type and required binding rules, by element path.
     *
     * @var array<int, array<string, mixed>>
     */
    public const PROFILE_RULES = <?php echo \DCarbone\PHPFHIR\Profiles\ProfileRules::export($profileClass->getRules(), 1); ?>;

    /**
     * Slices that can be matched by value, by sliced element path.
     *
     * @var array<int, array<string, mixed>>
     */
    public const PROFILE_SLICES = <?php echo \DCarbone\PHPFHIR\Profiles\ProfileRules::export($slices, 1); ?>;

    /**
     * Invariants declared by the profile.  Informational only; these are not evaluated.
     *
     * @var array<int, array<string, null|string>>
     */
    public const PROFILE_INVARIANTS = <?php echo \DCarbone\PHPFHIR\Profiles\ProfileRules::export($profileClass->getInvariants(), 1); ?>;

    /**
     * @return string
     */
    public static function _getProfileUrl(): string
    {
        return self::PROFILE_URL;
    }

    /**
     * Checks this instance against the profile.
     *
     * @return <?php echo $violation; ?>[] Empty if the instance conforms.
     */
    public function validate(): array
    {
        return <?php echo $validator; ?>::validate($this, self::PROFILE_RULES, self::PROFILE_SLICES);
    }

    /**
     * @return bool
     */
    public function isValid(): bool
    {
        return [] === $this->validate();
    }
<?php foreach ($accessors as $accessor) : ?>

    /**
<?php if ($accessor['single']) : ?>
     * @return null|object The "<?php echo $slices[$accessor['slice']]['name']; ?>" slice of <?php echo $slices[$accessor['slice']]['path']; ?>, if present.
     */
    public function <?php echo $accessor['method']; ?>(): null|object
    {
        foreach ($this-><?php echo $accessor['getter']; ?>() as $item) {
            if (<?php echo $validator; ?>::matchesSlice($item, self::PROFILE_SLICES[<?php echo $accessor['slice']; ?>]['match'])) {
                return $item;
            }
        }
        return null;
    }
<?php else : ?>
     * @return object[] The items of the "<?php echo $slices[$accessor['slice']]['name']; ?>" slice of <?php echo $slices[$accessor['slice']]['path']; ?>.
     */
    public function <?php echo $accessor['method']; ?>(): array
    {
        return array_values(array_filter(
            $this-><?php echo $accessor['getter']; ?>(),
            static fn(object $item): bool => <?php echo $validator; ?>::matchesSlice($item, self::PROFILE_SLICES[<?php echo $accessor['slice']; ?>]['match']),
        ));
    }
<?php endif; ?>
<?php endforeach; ?>
}
<?php return ob_get_clean();
