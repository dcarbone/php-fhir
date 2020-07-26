<?php

/*
 * Copyright 2018-2020 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Definition\Type $type */
/** @var \DCarbone\PHPFHIR\Definition\Type|null $parentType */

$typeClassName = $type->getClassName();
$primitiveType = $type->getPrimitiveType();
if (null !== $parentType) {
    $parentTypeKind = $parentType->getKind();
}

ob_start(); ?>
    /**
     * <?php echo $type->getClassName(); ?> Constructor
     * @param null|<?php echo $primitiveType->getPHPValueType(); ?> $value
     */
    public function __construct($value = null)
    {
        <?php if (null !== $parentType) :
            if ($parentTypeKind->isPrimitive() || $parentType->isValueContainer()) :
                ?>parent::__construct($value);<?php else: ?>if (null === $value) {
            parent::__construct();
        } elseif (is_scalar($value)) {
            parent::__construct();
            $this->setValue($value);
        } elseif (is_array($value)) {
            parent::__construct($value);
            if (isset($value['value'])) {
                $this->setValue($value['value']);
            }
        } else {
             throw new \InvalidArgumentException(sprintf(
                '<?php echo $typeClassName; ?>::_construct - $data expected to be null, <?php echo $primitiveType->getPHPValueType(); ?>, or array, %s seen',
                gettype($value)
            ));
        }<?php
            endif;
            else : ?>$this->setValue($value);<?php endif; ?>

    }
<?php return ob_get_clean();
