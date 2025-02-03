<?php declare(strict_types=1);

/*
 * Copyright 2025 Daniel Carbone (daniel.p.carbone@gmail.com)
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

/** @var \DCarbone\PHPFHIR\Version $version */
/** @var \DCarbone\PHPFHIR\Version\Definition\Type $type; */

$typeKind = $type->getKind();

ob_start();

foreach($type->getProperties()->getIterator() as $property) :
    if (null !== $property->getOverloadedProperty()) {
        continue;
    }

    $propType = $property->getValueFHIRType();
    if (null === $propType) {
        continue;
    }

    $setter = $property->getSetterName();
    $propTypeKind = $propType->getKind();
    $propTypeClass = $propType->getClassName();
    $propConst = $property->getFieldConstantName();
    $propConstExt = $property->getFieldConstantExtensionName();

    if ($propType->isPrimitiveOrListType() || $propType->hasPrimitiveOrListParent()) : ?>
        if ([] === $json) {
            return $type;
        }
        if (isset($json[self::<?php echo $propConst; ?>]) || array_key_exists(self::<?php echo $propConst; ?>, $json)) {
<?php   if ($property->isCollection()) : ?>
            if (is_array($json[self::<?php echo $propConst; ?>])) {
                foreach($json[self::<?php echo $propConst; ?>] as $v) {
                    $type-><?php echo $setter; ?>($v);
                }
            } else {
                $type-><?php echo $setter; ?>(<?php echo $property->getValueFHIRType()->getClassName(); ?>::jsonUnserialize($json[self::<?php echo $propConst; ?>]));
            }
<?php   else : ?>
            $type-><?php echo $setter; ?>($json[self::<?php echo $propConst; ?>]);
<?php   endif; ?>
        }
<?php elseif ($propType->getKind() === TypeKindEnum::PHPFHIR_XHTML) : ?>
        if (isset($json[self::<?php echo $propConst; ?>]) || array_key_exists(self::<?php echo $propConst; ?>, $json)) {
            $type-><?php echo $setter; ?>($json[self::<?php echo $propConst; ?>]);
        }
<?php elseif ($propType->isPrimitiveContainer() || $propType->hasPrimitiveContainerParent()) : ?>
        if (isset($json[self::<?php echo $propConst; ?>])
            || isset($json[self::<?php echo $propConstExt; ?>])
            || array_key_exists(self::<?php echo $propConst; ?>, $json)
            || array_key_exists(self::<?php echo $propConstExt; ?>, $json)) {
<?php if ($property->isCollection()) : ?>
            $value = (array)($json[self::<?php echo $propConst; ?>] ?? []);
            $ext = (array)($json[self::<?php echo $propConstExt; ?>] ?? []);
            $cnt = count($value);
            $extCnt = count($ext);
            if ($extCnt > $cnt) {
                $cnt = $extCnt;
            }
            for ($i = 0; $i < $cnt; $i++) {
                $type-><?php echo $setter; ?>(<?php echo $propTypeClass; ?>::jsonUnserialize(
                    [<?php echo $propTypeClass; ?>::FIELD_VALUE => $value[$i] ?? null] + ($ext[$i] ?? []),
                    $config,
                ));
            }
<?php else : ?>
            $value = $json[self::<?php echo $propConst; ?>] ?? null;
            $type-><?php echo $setter; ?>(<?php echo $propTypeClass; ?>::jsonUnserialize(
                (is_array($value) ? $value : [<?php echo $propTypeClass; ?>::FIELD_VALUE => $value]) + ($json[self::<?php echo $propConstExt; ?>] ?? []),
                $config,
            ));
<?php endif; ?>
        }
<?php elseif ($propTypeKind->isResourceContainer($version)) : ?>
        if (isset($json[self::<?php echo $propConst; ?>])) {
<?php   if ($property->isCollection()) : ?>
            $d = $json[self::<?php echo $propConst; ?>];
            if (!is_int(key($d))) {
                $d = [$d];
            }
            foreach($d as $v) {
                $typeClassName = <?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::getContainedTypeClassNameFromArray($v);
                unset($v[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE]);
                $type-><?php echo $setter; ?>($typeClassName::jsonUnserialize($v, $config));
            }
<?php   else : ?>
            $typeClassName = <?php echo PHPFHIR_VERSION_CLASSNAME_VERSION_TYPE_MAP; ?>::getContainedTypeClassNameFromArray($json[self::<?php echo $propConst; ?>]);
            $d = $json[self::<?php echo $propConst; ?>];
            unset($d[<?php echo PHPFHIR_CLASSNAME_CONSTANTS; ?>::JSON_FIELD_RESOURCE_TYPE]);
            $type-><?php echo $setter; ?>($typeClassName::jsonUnserialize($d, $config));
<?php   endif; ?>
        }
<?php else : ?>
        if (isset($json[self::<?php echo $propConst; ?>]) || array_key_exists(self::<?php echo $propConst; ?>, $json)) {
<?php   if ($property->isCollection()) : ?>
            $vs = $json[self::<?php echo $propConst; ?>];
            if (!is_int(key($vs))) {
                $vs = [$vs];
            }
            foreach($vs as $v) {
                $type-><?php echo $setter; ?>(<?php echo $propTypeClass; ?>::jsonUnserialize($v, $config));
            }
<?php       else : ?>
            $type-><?php echo $setter; ?>(<?php echo $propTypeClass; ?>::jsonUnserialize($json[self::<?php echo $propConst; ?>], $config));
<?php   endif; ?>
        }
<?php endif;
endforeach; ?>
        return $type;
    }
<?php return ob_get_clean();
