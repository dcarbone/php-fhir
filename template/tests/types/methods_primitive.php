<?php
/*
 * Copyright 2018-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\PrimitiveTypeEnum;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

$primitiveType = $type->getPrimitiveType();

// TODO: more different types of strvals...
switch ($primitiveType->getValue()) {
    case PrimitiveTypeEnum::INTEGER:
    case PrimitiveTypeEnum::POSITIVE_INTEGER:
        $strVal = '10';
        break;

    case PrimitiveTypeEnum::NEGATIVE_INTEGER:
        $strVal = '-10';
        break;

    case PrimitiveTypeEnum::DECIMAL:
        $strVal = '10.5';
        break;

    case PrimitiveTypeEnum::UNSIGNED_INTEGER:
        $strVal = (string)PHP_INT_MAX;
        break;

    case PrimitiveTypeEnum::BOOLEAN:
        $strVal = 'true';
        break;

    default:
        $strVal = 'randomstring';
}

ob_start(); ?>

        public function testCanConstructWithString()
        {
            $n = new <?php echo $type->getClassName(); ?>('<?php echo $strVal; ?>');
            $this->assertEquals('<?php echo $strVal; ?>', (string)$n);
        }

        public function testCanSetValueFromString()
        {
            $n = new <?php echo $type->getClassName(); ?>;
            $n->setValue('<?php echo $strVal; ?>');
            $this->assertEquals('<?php echo $strVal; ?>', (string)$n);
        }
<?php return ob_get_clean();