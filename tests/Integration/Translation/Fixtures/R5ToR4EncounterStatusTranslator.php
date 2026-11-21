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
use DCarbone\PHPFHIRGenerated\Versions\R4\Types\FHIRResource\FHIRDomainResource\FHIREncounter as R4Encounter;
use DCarbone\PHPFHIRGenerated\Versions\R5\Types\FHIRBase\FHIRResource\FHIRDomainResource\FHIREncounter as R5Encounter;

/**
 * Example user translator: the default translator only applies ConceptMaps to Coding values, so bare "code"
 * fields such as Encounter.status are copied verbatim.  This composes the default translator and then fixes up
 * the R5-only status codes.
 */
final class R5ToR4EncounterStatusTranslator implements TypeTranslatorInterface
{
    private const STATUS_MAP = [
        'on-hold' => 'onleave',
        'discharged' => 'finished',
        'completed' => 'finished',
        'discontinued' => 'cancelled',
    ];

    public function __construct(
        private readonly GenericTypeTranslator $default = new GenericTypeTranslator(),
    ) {}

    public function translate(TypeInterface $source, TranslationContext $context): null|TypeInterface
    {
        $target = $this->default->translate($source, $context);
        if (!$source instanceof R5Encounter || !$target instanceof R4Encounter || null === $source->getStatus()) {
            return $target;
        }
        $status = (string)$source->getStatus();
        if (isset(self::STATUS_MAP[$status])) {
            $target->setStatus(self::STATUS_MAP[$status]);
        }
        return $target;
    }
}
