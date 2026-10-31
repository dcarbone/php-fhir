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

use DCarbone\PHPFHIR\Profiles\Rules\ProfileInvariant;
use DCarbone\PHPFHIR\Utilities\CopyrightUtils;
use DCarbone\PHPFHIR\Utilities\DocumentationUtils;
use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var string $copyright */
/** @var \DCarbone\PHPFHIR\Profiles\ProfileClass $profileClass */

$profile = $profileClass->getProfile();
$imports = $profileClass->getImports();
$code = $profileClass->getValidationCode();
$accessors = $profileClass->getSliceAccessors();
$profileInterface = $profileClass->getProfileInterfaceName();
$isRoot = null === $profileClass->getParent();

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $profileClass->getNamespace(); ?>;

<?php echo CopyrightUtils::appendProfileCopyright($copyright, $profileClass->getCopyrightNotice()); ?>


<?php echo ImportUtils::compileImportStatements($imports); ?>

/**
 * Class <?php echo $profileClass->getClassName(); ?>

 *
 * Profile: <?php echo DocumentationUtils::escapeCommentLine($profile->getUrl()); ?>

<?php if (null !== $profile->getTitle()) : ?> * Title: <?php echo DocumentationUtils::escapeCommentLine($profile->getTitle()); ?>

<?php endif; ?>
 * Constrains: <?php echo DocumentationUtils::escapeCommentLine($profile->getType()); ?>

 */
class <?php echo $profileClass->getClassName(); ?> extends <?php echo $profileClass->getExtendsName(); ?><?php if (null !== $profileInterface) : ?> implements <?php echo $profileInterface; ?><?php endif; ?>

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
    public const PROFILE_INVARIANTS = <?php echo pretty_var_export(array_map(static fn(ProfileInvariant $i): array => $i->toArray(), $profileClass->getRules()->getInvariants()), 1); ?>;

    private const _PROFILE_VALIDATION_RULES = <?php echo pretty_var_export($code->rules, 1, rawKeys: true); ?>;
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
     * @return null|string
     */
    public static function _getProfileVersion(): null|string
    {
        return self::PROFILE_VERSION;
    }
<?php if ($isRoot) : ?>

    /**
     * Executes only the validation rules added or tightened by this profile and the profiles derived from it.
     *
     * @return array
     */
    public function _getProfileValidationErrors(): array
    {
        $combined = $this->_getCombinedValidationRules();
        $rules = [];
        foreach (static::_getProfileValidationRuleNames() as $field => $names) {
            foreach ($names as $rule => $_) {
                if (isset($combined[$field]) && array_key_exists($rule, $combined[$field])) {
                    $rules[$field][$rule] = $combined[$field][$rule];
                }
            }
        }
        return $this->_collectValidationErrors($rules, false);
    }
<?php endif; ?>

    /**
     * Returns the rules set by this profile and the profiles it derives from, as a map of field => [rule => true].
     *
     * @return array<string, array<string, bool>>
     */
    protected static function _getProfileValidationRuleNames(): array
    {
        $out = <?php echo $isRoot ? '[]' : 'parent::_getProfileValidationRuleNames()'; ?>;
        foreach (self::_PROFILE_VALIDATION_RULES as $field => $rules) {
            foreach ($rules as $rule => $_) {
                $out[$field][$rule] = true;
            }
        }
        return $out;
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
<?php if ($accessor->isSingle()) : ?>
     * @return null|<?php echo $accessor->getMatcher()->getClassName(); ?> The "<?php echo DocumentationUtils::escapeCommentLine($accessor->getSlice()->getName()); ?>" slice of <?php echo DocumentationUtils::escapeCommentLine($accessor->getSlice()->getPath()); ?>, if present.
     */
    public function <?php echo $accessor->getMethod(); ?>(): null|<?php echo $accessor->getMatcher()->getClassName(); ?>

    {
        foreach ($this-><?php echo $accessor->getGetter(); ?>() as $item) {
            if (<?php echo $profileClass->getValueMatcherName(); ?>::matches($item, self::<?php echo $accessor->getMatcher()->getConstant(); ?>)) {
                return $item;
            }
        }
        return null;
    }
<?php else : ?>
     * @return <?php echo $accessor->getMatcher()->getClassName(); ?>[] The items of the "<?php echo DocumentationUtils::escapeCommentLine($accessor->getSlice()->getName()); ?>" slice of <?php echo DocumentationUtils::escapeCommentLine($accessor->getSlice()->getPath()); ?>.
     */
    public function <?php echo $accessor->getMethod(); ?>(): array
    {
        $matched = [];
        foreach ($this-><?php echo $accessor->getGetter(); ?>() as $item) {
            if (<?php echo $profileClass->getValueMatcherName(); ?>::matches($item, self::<?php echo $accessor->getMatcher()->getConstant(); ?>)) {
                $matched[] = $item;
            }
        }
        return $matched;
    }
<?php endif; ?>
<?php endforeach; ?>
}
<?php return ob_get_clean();
