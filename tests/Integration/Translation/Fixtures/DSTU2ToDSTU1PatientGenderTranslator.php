<?php declare(strict_types=1);

namespace DCarbone\PHPFHIRTests\Integration\Translation\Fixtures;

/*
 * Copyright 2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIRGenerated\Translation\GenericTypeTranslator;
use DCarbone\PHPFHIRGenerated\Translation\TranslationContext;
use DCarbone\PHPFHIRGenerated\Translation\TypeTranslatorInterface;
use DCarbone\PHPFHIRGenerated\Types\TypeInterface;
use DCarbone\PHPFHIRGenerated\Versions\DSTU1\Types\FHIRIdPrimitive as DSTU1IdPrimitive;
use DCarbone\PHPFHIRGenerated\Versions\DSTU1\Types\FHIRElement\FHIRCodeableConcept as DSTU1CodeableConcept;
use DCarbone\PHPFHIRGenerated\Versions\DSTU1\Types\FHIRElement\FHIRCoding as DSTU1Coding;
use DCarbone\PHPFHIRGenerated\Versions\DSTU1\Types\FHIRElement\FHIRExtension as DSTU1Extension;
use DCarbone\PHPFHIRGenerated\Versions\DSTU1\Types\FHIRResource\FHIRPatient as DSTU1Patient;
use DCarbone\PHPFHIRGenerated\Versions\DSTU2\Types\FHIRResource\FHIRDomainResource\FHIRPatient as DSTU2Patient;

/**
 * Example user translator: DSTU1 represents Patient.gender as a CodeableConcept, which the default translator will
 * not build from a bare code.  This removes gender before default translation and builds the CodeableConcept itself.
 */
final class DSTU2ToDSTU1PatientGenderTranslator implements TypeTranslatorInterface
{
    public const GENDER_SYSTEM = 'http://hl7.org/fhir/v3/AdministrativeGender';

    private const GENDER_MAP = [
        'female' => ['F', 'Female'],
        'male' => ['M', 'Male'],
        'other' => ['UN', 'Undifferentiated'],
    ];

    public function __construct(
        private readonly GenericTypeTranslator $default = new GenericTypeTranslator(),
    ) {}

    public function translate(TypeInterface $source, TranslationContext $context): null|TypeInterface
    {
        if (!$source instanceof DSTU2Patient || null === $source->getGender()) {
            return $this->default->translate($source, $context);
        }
        $sourceGender = $source->getGender();
        $gender = (string)$sourceGender;
        if (!isset(self::GENDER_MAP[$gender])) {
            return $this->default->translate($source, $context);
        }
        $withoutGender = clone $source;
        $withoutGender->setGender(null);
        $target = $this->default->translate($withoutGender, $context);
        if ($target instanceof DSTU1Patient) {
            [$code, $display] = self::GENDER_MAP[$gender];
            $targetGender = new DSTU1CodeableConcept();
            $sourceId = $sourceGender->getId();
            if (null !== $sourceId) {
                $translatedId = $context->translateField('gender.id', $sourceId, DSTU1IdPrimitive::class);
                if (!$translatedId instanceof DSTU1IdPrimitive) {
                    throw new \UnexpectedValueException('Translated Patient.gender id has an unexpected type.');
                }
                $targetGender->setId($translatedId);
            }
            $extensions = [];
            foreach ($sourceGender->getExtension() as $extension) {
                if (!$extension instanceof TypeInterface) {
                    throw new \UnexpectedValueException('Patient.gender extension has an unexpected type.');
                }
                $translated = $context->translateField('gender.extension', $extension, DSTU1Extension::class);
                if (null !== $translated) {
                    if (!$translated instanceof DSTU1Extension) {
                        throw new \UnexpectedValueException('Translated Patient.gender extension has an unexpected type.');
                    }
                    $extensions[] = $translated;
                }
            }
            $targetGender->setExtension(...$extensions);
            $target->setGender($targetGender->addCoding(
                (new DSTU1Coding())->setSystem(self::GENDER_SYSTEM)->setCode($code)->setDisplay($display),
            ));
        }
        return $target;
    }
}
