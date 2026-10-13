<?php declare(strict_types=1);

namespace DCarbone\PHPFHIR\Utilities;

/*
 * Copyright 2016-2026 Daniel Carbone (daniel.p.carbone@gmail.com)
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

use DCarbone\PHPFHIR\Config;
use DCarbone\PHPFHIR\Version\SourceMetadata;

class CopyrightUtils
{
    public static function compileFullCopyrightComment(Config $config, SourceMetadata $sourceMetadata): string
    {
        return sprintf(
            "/*!\n * %s\n *\n * FHIR Copyright Notice:\n *\n * %s\n */",
            implode("\n * ", $config->getPHPFHIRCopyright()),
            implode("\n * ", $sourceMetadata->getFHIRCopyright())
        );
    }

    /**
     * Appends a profile's own copyright statement to a comment produced by {@see self::compileFullCopyrightComment()}.
     */
    public static function appendProfileCopyright(string $comment, null|string $profileCopyright): string
    {
        if (null === $profileCopyright) {
            return $comment;
        }
        $lines = [];
        foreach (preg_split('{\R}', str_replace('*/', '* /', $profileCopyright)) as $line) {
            $lines[] = rtrim(' * ' . $line);
        }
        return substr($comment, 0, -strlen(' */'))
            . " *\n * Profile Copyright Notice:\n *\n"
            . implode("\n", $lines)
            . "\n */";
    }
}
