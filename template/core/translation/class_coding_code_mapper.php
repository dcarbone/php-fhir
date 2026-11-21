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

use DCarbone\PHPFHIR\Utilities\ImportUtils;

/** @var \DCarbone\PHPFHIR\Config $config */
/** @var \DCarbone\PHPFHIR\CoreFile $coreFile */

$coreFiles = $config->getCoreFiles();
$typeInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TYPES_INTERFACE_TYPE);
$fieldMetadataClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_FIELD_METADATA);
$codeMapClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_CODE_MAP);
$hopMapInterface = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_INTERFACE_HOP_MAP);
$contextClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_CONTEXT);
$codeMapResultClass = $coreFiles->getCoreFileByEntityName(PHPFHIR_TRANSLATION_CLASSNAME_CODE_MAP_RESULT);
$imports = $coreFile->getImports();
$imports->addCoreFileImports($typeInterface, $codeMapClass, $hopMapInterface, $contextClass, $codeMapResultClass);

ob_start();
echo '<?php ';?>declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

final class <?php echo $coreFile; ?>
{
    /**
     * @param array<string, <?php echo $fieldMetadataClass->getFullyQualifiedName(true); ?>> $sourceFields
     */
    public function map(
        TypeInterface $source,
        array $sourceFields,
        HopMapInterface $hopMap,
        TranslationContext $context,
    ): null|CodeMapResult {
        $codeMaps = $hopMap->getCodeMaps();
        if ([] === $codeMaps) {
            return null;
        }

        $sourceSystem = $this->getStringFieldValue($source, $sourceFields, 'system');
        $sourceCode = $this->getStringFieldValue($source, $sourceFields, 'code');
        if (null === $sourceSystem || null === $sourceCode) {
            return null;
        }

        $sourceSystemMatched = false;
        /** @var array<string, CodeMapResult> $candidateTargets */
        $candidateTargets = [];
        foreach ($codeMaps as $codeMap) {
            if (!$codeMap instanceof CodeMap) {
                throw new \UnexpectedValueException(sprintf(
                    'Hop map returned %s for code maps; expected %s.',
                    get_debug_type($codeMap),
                    CodeMap::class,
                ));
            }
            if ($codeMap->getSourceSystem() !== $sourceSystem) {
                continue;
            }
            $sourceSystemMatched = true;
            // A source system can have several maps; only maps that translate this code are candidates.
            if ($codeMap->hasSourceCode($sourceCode)
                && null !== ($candidateCode = $codeMap->getTargetCode($sourceCode))) {
                $targetSystem = $codeMap->getTargetSystem();
                $candidateTargets[$targetSystem . '|' . $candidateCode] = new CodeMapResult(
                    $sourceSystem,
                    $targetSystem,
                    $candidateCode,
                );
            }
        }
        if (!$sourceSystemMatched) {
            return null;
        }
        if (1 !== count($candidateTargets)) {
            $context->handleUnmappableField('code', get_debug_type($sourceCode));
            return new CodeMapResult($sourceSystem, null, null);
        }

        foreach ($candidateTargets as $candidateTarget) {
            return $candidateTarget;
        }

        throw new \LogicException('Expected a unique candidate target after validating its count.');
    }

    /**
     * @param array<string, <?php echo $fieldMetadataClass->getFullyQualifiedName(true); ?>> $fields
     */
    private function getStringFieldValue(TypeInterface $owner, array $fields, string $fieldName): null|string
    {
        if (!isset($fields[$fieldName])) {
            return null;
        }
        $getter = $fields[$fieldName]->getGetter();
        if (!method_exists($owner, $getter)) {
            throw new \UnexpectedValueException(sprintf(
                'Generated type "%s" is missing field getter "%s()".',
                $owner::class,
                $getter,
            ));
        }
        $value = $owner->{$getter}();
        if (null === $value) {
            return null;
        }
        if (!$value instanceof TypeInterface || !method_exists($value, 'getValue')) {
            throw new \UnexpectedValueException(sprintf(
                'FHIR field "%s::%s()" must return a value-bearing type.',
                $owner::class,
                $getter,
            ));
        }
        // Elements wrap primitives (e.g. uri -> uri-primitive), so unwrap until a scalar is reached.
        do {
            $value = $value->getValue();
        } while ($value instanceof TypeInterface && method_exists($value, 'getValue'));
        if (null !== $value && !is_string($value)) {
            throw new \UnexpectedValueException(sprintf(
                'FHIR field "%s::%s()" must contain a string value.',
                $owner::class,
                $getter,
            ));
        }
        return $value;
    }
}
<?php return ob_get_clean();
