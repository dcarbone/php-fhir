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

use DCarbone\PHPFHIR\Enum\PrimitiveType;

/** @var \DCarbone\PHPFHIR\Config\VersionConfig $config */
/** @var \DCarbone\PHPFHIR\Definition\Types $types */
/** @var \DCarbone\PHPFHIR\Definition\Type $type */

$primitiveType = $type->getPrimitiveType();

// TODO: more different types of strvals...
$strVals = match ($primitiveType) {
    PrimitiveType::INTEGER, PrimitiveType::POSITIVE_INTEGER, PrimitiveType::INTEGER64 => ['10', '1,000'],
    PrimitiveType::NEGATIVE_INTEGER => ['-10', '-1,000'],
    PrimitiveType::DECIMAL => ['10.5', '1,000.3333'],
    PrimitiveType::UNSIGNED_INTEGER => [(string)PHP_INT_MAX, '1,000'],
    PrimitiveType::BOOLEAN => ['true'],
    default => ['randomstring'],
};

ob_start(); ?>
    public function testCanConstructWithString()
    {
<?php foreach($strVals as $strVal) : ?>
        {
            $n = new <?php echo $type->getClassName(); ?>('<?php echo $strVal; ?>');
            $this->assertEquals('<?php echo $strVal; ?>', (string)$n);
        }
<?php endforeach; ?>
    }

    public function testCanSetValueFromString()
    {
<?php foreach($strVals as $strVal) : ?>
        {
            $n = new <?php echo $type->getClassName(); ?>;
            $n->setValue('<?php echo $strVal; ?>');
            $this->assertEquals('<?php echo $strVal; ?>', (string)$n);
        }
<?php endforeach; ?>
    }
<?php return ob_get_clean();