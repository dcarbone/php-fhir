<?php declare(strict_types=1);

/*
 * Copyright 2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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
$coreTestFiles = $config->getCoreTestFiles();
$imports = $coreFile->getImports();

$mockPrimitiveType = $coreTestFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_STRING_PRIMITIVE_TYPE);
$mockElementType = $coreTestFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_ELEMENT_TYPE);
$mockPrimitiveContainerType = $coreTestFiles->getCoreFileByEntityName(PHPFHIR_TEST_CLASSNAME_MOCK_PRIMITIVE_CONTAINER_TYPE);

$imports->addCoreFileImports(
    $mockPrimitiveType,
    $mockElementType,
    $mockPrimitiveContainerType,
);

ob_start();
echo '<?php'; ?> declare(strict_types=1);

namespace <?php echo $coreFile->getFullyQualifiedNamespace(false); ?>;

<?php echo $config->getBasePHPFHIRCopyrightComment(true); ?>

<?php echo ImportUtils::compileImportStatements($imports); ?>

trait <?php echo $coreFile; ?>

{
    protected static function _buildFieldsFromJSON(\stdClass $decoded): array
    {
        $fields = [];

        foreach($decoded as $name => $value) {
            // quick check for collection field.
            $collection = is_array($value);

            // no way to handle ambiguous fields right now.
            if (null === $value || ($collection && [] === $value)) {
                continue;
            }

            // get "example" value.
            $exValue = $collection ? $value[0] : $value;

            // initialize field definition
            $field = [
                'class' => match(true) {
                    is_scalar($exValue) => <?php echo $mockPrimitiveType; ?>::class,
                    isset($exValue->value) => <?php echo $mockPrimitiveContainerType; ?>::class,
                    default => <?php echo $mockElementType; ?>::class,
                },
                'collection' => $collection,
            ];

            if (is_scalar($exValue)) {
                // If the value is a scalar, simply set the field's value to the
                // json value and move on.  The mock constructor will handle the rest of initialization.
                $field['value'] = $value;
            } else if ($collection) {
                // For collection values, we'll need to iterate over each to be sure that each value
                // has all appropriate field definitions.
                //
                // Eventually maybe merge all the defs, but meh.
                $field['value'] = [];
                foreach($value as $v) {
                    $field['value'][] = new $field['class'](
                        name: $name,
                        fields: static::_buildFieldsFromJson($v)
                    );
                }
            } else {
                $field['value'] = new $field['class'](
                    name: $name,
                    fields: static::_buildFieldsFromJson($value)
                );
            }

            $fields[$name] = $field;
        }

        return $fields;
    }
}
<?php return ob_get_clean();

