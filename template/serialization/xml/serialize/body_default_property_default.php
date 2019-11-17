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

/** @var bool $isCollection */
/** @var bool $isValueProperty */
/** @var string $propertyConstName */
/** @var string $getter */

ob_start();
?>

<?php if ($isCollection) : ?>
        if ([] !== ($vs = $this-><?php echo $getter; ?>())) {
            foreach($vs as $v) {
                if (null === $v) {
                    continue;
                }
                $v->xmlSerialize($sxe->addChild(self::<?php echo $propertyConstName; ?>, null, $v->_getFHIRXMLNamespace()));
            }
        }<?php else : ?>
        if (null !== ($v = $this-><?php echo $getter; ?>())) {
<?php if ($isValueProperty) : ?>
            $sxe->addAttribute(self::<?php echo $propertyConstName; ?>, (string)$v);
            $v->xmlSerialize($sxe->addChild(self::<?php echo $propertyConstName; ?>, null, $v->_getFHIRXMLNamespace()));
<?php else : ?>
            $v->xmlSerialize($sxe->addChild(self::<?php echo $propertyConstName; ?>, null, $v->_getFHIRXMLNamespace()));
<?php endif; ?>
        }<?php endif;
return ob_get_clean();
