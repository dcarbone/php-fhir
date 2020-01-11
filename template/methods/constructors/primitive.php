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

$primitiveType = $type->getPrimitiveType();

ob_start(); ?>
    /**
     * <?php echo $type->getClassName(); ?> Constructor
     * @param null|<?php echo $primitiveType->getPHPValueType(); ?> $value
     */
    public function __construct($value = null)
    {
        <?php if (null !== $parentType) : ?>parent::__construct($value);<?php else : ?>$this->setValue($value);<?php endif; ?>

    }
<?php return ob_get_clean();
