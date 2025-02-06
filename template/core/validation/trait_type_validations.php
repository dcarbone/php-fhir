<?php declare(strict_types=1);

/*
 * Copyright 2024-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();
$validatorClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_VALIDATION_CLASSNAME_VALIDATOR);
$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);

$imports = $coreFile->getImports();
$imports->addCoreFileImports(
    $typeInterface,
    $validatorClass,
);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

trait <?php echo $coreFile; ?>

{
    /**
     * Map of custom vlaidation rules for a given Type.
     *
     * @var array
     */
    private array $_customValidationRules = [];

    /**
     * Returns the pre-defined validations as extracted from the source FHIR schema.
     *
     * @return array
     */
    public function _getFHIRValidationRules(): array
    {
        return self::_FHIR_VALIDATION_RULES;
    }

    /**
     * Return all custom validation rules
     *
     * @return array
     */
    public function _getCustomValidationRules(): array
    {
        return $this->_customValidationRules;
    }

    /**
     * Returns all validation rules for this type, with custom validations overridding those extracted from the
     * FHIR schema during generation.
     *
     * The returned map has the structure: ["fieldname" => ["rule" => {constraint}]].
     *
     * @return array
     */
    public function _getCombinedValidationRules(): array
    {
        $out = self::_FHIR_VALIDATION_RULES;
        foreach ($this->_customValidationRules as $field => $rules) {
            $out[$field] = array_merge($out[$field] ?? [], $rules);
        }
        return $out;
    }

    /**
     * Set the entire validation rule map for a given field
     *
     * @param string $field Field name
     * @param array $rules Map of ["rule" => {constraint}] for this field
     */
    public function _setFieldValidationRules(string $field, array $rules): void
    {
        $this->_customValidationRules[$field] = $rules;
    }

    /**
     * Set a specific rule's constraints for a given field.  Set $constraint to null to prevent a given rule from
     * being run.
     *
     * @param string $field Field name
     * @param string $rule Rule name
     * @param mixed $constraint Rule constraint, value differs depending upon rule
     */
    public function _setFieldValidationRule(string $field, string $rule, mixed $constraint): void
    {
        if (!isset($this->_customValidationRules[$field])) {
            $this->_customValidationRules[$field] = [];
        }
        $this->_customValidationRules[$field][$rule] = $constraint;
    }

    /**
     * Executes all defined validation rules for this type, returning a map of validation failures.
     *
     * The returned map is keyed by the field and valued by a list of validation failures.  An empty array must be seen
     * as no validation errors occurring.
     *
     * @return array
     */
    public function _getValidationErrors(): array 
    {
        $errs = [];
        foreach ($this->_getCombinedValidationRules() as $field => $rules) {
            $v = $this->{$field} ?? null;
            foreach ($rules as $rule => $constraint) {
                $err = <?php echo $validatorClass; ?>::runRule($this, $field, $rule, $constraint, $v);
                if (null !== $err) {
                    if (!isset($errs[$field])) {
                        $errs[$field] = [];
                    }
                    $errs[$field][] = $err;
                }
            }
            if ($v instanceof <?php echo $typeInterface; ?>) {
                $typeErrs = $v->_getValidationErrors();
                if ([] !== $typeErrs) {
                    foreach($typeErrs as $subField => $subErrs) {
                        $errs["{$field}.{$subField}"] = $subErrs;
                    }
                }
            } else if (is_array($v)) {
                foreach($v as $i => $vv) {
                    $typeErrs = $vv->_getValidationErrors();
                    if ([] !== $typeErrs) {
                        foreach($typeErrs as $subField => $subErrs) {
                            $errs["{$field}.{$i}.{$subField}"] = $subErrs;
                        }
                    }
                }
            }
        }
        return $errs;
    }
}
<?php return ob_get_clean();
