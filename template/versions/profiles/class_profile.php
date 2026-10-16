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
$imports = $profileClass->getImports();
$code = $profileClass->getValidationCode();
$accessors = $profileClass->getSliceAccessors();
$slices = $profileClass->getSlices();

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $profileClass->getNamespace(); ?>;

<?php echo \DCarbone\PHPFHIR\Utilities\CopyrightUtils::appendProfileCopyright($copyright, $profileClass->getCopyrightNotice()); ?>


<?php echo \DCarbone\PHPFHIR\Utilities\ImportUtils::compileImportStatements($imports); ?>

/**
 * Class <?php echo $profileClass->getClassName(); ?>

 *
 * Profile: <?php echo $profile->getUrl(); ?>

<?php if (null !== $profile->getTitle()) : ?> * Title: <?php echo str_replace('*/', '* /', $profile->getTitle()); ?>

<?php endif; ?>
 * Constrains: <?php echo $profile->getType(); ?>

 */
class <?php echo $profileClass->getClassName(); ?> extends <?php echo $profileClass->getExtendsName(); ?>

{
    public const PROFILE_URL = <?php echo var_export($profile->getUrl(), true); ?>;
    public const PROFILE_VERSION = <?php echo var_export($profile->getVersion(), true); ?>;
    public const PROFILE_NAME = <?php echo var_export($profile->getName(), true); ?>;
    public const PROFILE_BASE_TYPE = <?php echo var_export($profile->getType(), true); ?>;

    /**
     * Invariants declared by the profile.  Informational only; these are not evaluated.
     *
     * @var array<int, array<string, null|string>>
     */
    public const PROFILE_INVARIANTS = <?php echo \DCarbone\PHPFHIR\Profiles\ProfileRules::export($profileClass->getInvariants(), 1); ?>;

    private const _PROFILE_VALIDATION_RULES = <?php echo \DCarbone\PHPFHIR\Profiles\ProfileRules::exportFieldRules($code->rules, 1); ?>;
<?php if ('' !== $code->constants) : ?>

<?php echo $code->constants; ?>

<?php endif; ?>

    /**
     * @return string
     */
    public static function _getProfileUrl(): string
    {
        return self::PROFILE_URL;
    }

    /**
     * Returns the type's validation rules with those of the profile applied on top.  A profile may only constrain its
     * base: cardinalities are tightened, never loosened.
     *
     * @return array
     */
    public function _getCombinedValidationRules(): array
    {
        $out = parent::_getCombinedValidationRules();
        foreach (self::_PROFILE_VALIDATION_RULES as $field => $rules) {
            $out[$field] = <?php echo $profileClass->getValidatorName(); ?>::tighten($out[$field] ?? [], $rules);
        }
        return $out;
    }
<?php foreach ($accessors as $accessor) : ?>

    /**
<?php if ($accessor['single']) : ?>
     * @return null|<?php echo $accessor['class']; ?> The "<?php echo $slices[$accessor['slice']]['name']; ?>" slice of <?php echo $slices[$accessor['slice']]['path']; ?>, if present.
     */
    public function <?php echo $accessor['method']; ?>(): null|<?php echo $accessor['class']; ?>

    {
        foreach ($this-><?php echo $accessor['getter']; ?>() as $item) {
            if (<?php echo $profileClass->getValueMatcherName(); ?>::matches($item, self::<?php echo $accessor['constant']; ?>)) {
                return $item;
            }
        }
        return null;
    }
<?php else : ?>
     * @return <?php echo $accessor['class']; ?>[] The items of the "<?php echo $slices[$accessor['slice']]['name']; ?>" slice of <?php echo $slices[$accessor['slice']]['path']; ?>.
     */
    public function <?php echo $accessor['method']; ?>(): array
    {
        $matched = [];
        foreach ($this-><?php echo $accessor['getter']; ?>() as $item) {
            if (<?php echo $profileClass->getValueMatcherName(); ?>::matches($item, self::<?php echo $accessor['constant']; ?>)) {
                $matched[] = $item;
            }
        }
        return $matched;
    }
<?php endif; ?>
<?php endforeach; ?>
}
<?php return ob_get_clean();
