<?php

/*
 * Copyright 2016-2019 Daniel Carbone (daniel.p.carbone@gmail.com)
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
/** @var \DCarbone\PHPFHIR\Enum\TypeKindEnum $typeKind */

$primitiveTypeString = (string)$primitiveType;

ob_start(); ?>
    /**
     * @return mixed
     */
    public function jsonSerialize()
    {
<?php if (PrimitiveTypeEnum::DATE === $primitiveTypeString) : ?>
        return null === ($value = $this->getValue()) ? null : $value->format($this->valueFormat);
<?php elseif (PrimitiveTypeEnum::DATETIME === $primitiveTypeString) : ?>
        return null === ($value = $this->getValue()) ? null : $value->format($this->valueFormat);
<?php elseif (PrimitiveTypeEnum::TIME === $primitiveTypeString) : ?>
        return null === ($value = $this->getValue()) ? null : $value->format(self::TIME_FORMAT);
<?php elseif (PrimitiveTypeEnum::INSTANT === $primitiveTypeString) : ?>
        return null === ($value = $this->getValue()) ? null : $value->format(self::INSTANT_FORMAT);
<?php else : ?>
        return null === ($value = $this->getValue()) ? null : $value;
<?php endif; ?>
    }

<?php return ob_get_clean();
