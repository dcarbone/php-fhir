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

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Property $property */
/** @var int $i */

$setter = $property->getSetterName();
$propertyName = $property->getName();
$propertyConst = $property->getFieldConstantName();

ob_start();
if ($i > 0) : ?> else<?php else : ?>            <?php endif; ?>if (self::<?php echo $propertyConst; ?> === $childName) {
                foreach ($n->children() as $nn) {
                    $typeClassName = <?php echo PHPFHIR_CLASSNAME_VERSION; ?>::getTypeMap()->getContainedTypeClassFromXML($nn);
                    $type-><?php echo $setter; ?>(new $typeClassName($nn, $config));
                }
            }<?php return ob_get_clean();
