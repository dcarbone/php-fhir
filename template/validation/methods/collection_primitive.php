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

/** @var \DCarbone\PHPFHIR\Definition\Property $property */

ob_start(); ?>
        if (isset($validationRules[self::FIELD_VALUE]) && [] !== ($vs = $this-><?php echo $property->getGetterName(); ?>())) {
            foreach($vs as $i => $v) {
                $err = $this->_performValidation(<?php echo $property->getMemberOf()->getTypeNameConst(true); ?>, self::<?php echo $property->getFieldConstantName(); ?>, $rule, $constraint, $v);
                if (null !== $err) {
                    $key = sprintf('%s.%d', self::<?php echo $property->getFieldConstantName(); ?>, $i);
                    if (!isset($errs[$key])) {
                        $errs[$key] = [];
                    }
                    $errs[$key][$rule] = $err;
                }
            }
        }
<?php
return ob_get_clean();
