<?php declare(strict_types=1);

/*
 * Copyright 2016-2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Enum\TypeKindEnum;
use DCarbone\PHPFHIR\Utilities\TypeHintUtils;

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type */

$typeKind = $type->getKind();

ob_start(); ?>
    /**
     * @return <?php echo TypeHintUtils::primitivePHPValueTypeHint($version, $type->getPrimitiveType(), true); ?>

     */
    public function jsonSerialize(): mixed
    {
<?php if ($typeKind === TypeKindEnum::PRIMITIVE && str_contains($type->getFHIRName(), 'unsigned')) : ?>
        return intval($this->getValue(), 10);
<?php else : ?>
        return $this->getValue();
<?php endif; ?>
    }
<?php return ob_get_clean();
