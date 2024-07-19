<?php declare(strict_types=1);

/*
 * Copyright 2018-2024 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$properties = $type->getLocalProperties()->allSortedPropertiesIterator();

ob_start(); ?>
    /**
     * Validation map for fields in type <?php echo $type->getFHIRName(); ?>

     * @var array
     */
    private const _VALIDATION_RULES = [<?php foreach ($properties as $property) :
    $validationMap = $property->buildValidationMap();
    if ([] !== $validationMap) : ?>

        self::<?php echo $property->getFieldConstantName(); ?> => [
<?php foreach($validationMap as $k => $v) : ?>
            PHPFHIRConstants::<?php echo $k; ?> => <?php
            switch ($k) :
                case PHPFHIR_VALIDATION_ENUM_NAME:
                    echo '[';
                    foreach($v as $vv) :
                        echo "'{$vv}',";
                    endforeach;
                    echo ']';
                    break;
                default:
                    var_export($v);
            endswitch; ?>,
<?php endforeach; ?>
        ],
<?php endif;
endforeach; ?>
    ];
<?php
return ob_get_clean();
