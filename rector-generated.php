<?php declare(strict_types=1);

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

use Rector\CodeQuality\Rector\FunctionLike\SimplifyUselessVariableRector;
use Rector\Config\RectorConfig;
use Rector\DeadCode\Rector\Array_\RemoveDuplicatedArrayKeyRector;
use Rector\DeadCode\Rector\Assign\RemoveDoubleAssignRector;
use Rector\DeadCode\Rector\Assign\RemoveDoubleSelfAssignRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveDuplicatedReturnSelfDocblockRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveMixedDocblockOverruledByNativeTypeRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveNullTagValueNodeRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveReturnTagIncompatibleWithNativeTypeRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnExprInConstructRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessUnionReturnDocblockRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveVoidDocblockFromMagicMethodRector;
use Rector\DeadCode\Rector\Expression\RemoveDeadStmtRector;
use Rector\DeadCode\Rector\Node\RemoveNonExistingVarAnnotationRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\DeadCode\Rector\Stmt\RemoveUnreachableStatementRector;
use Rector\Php55\Rector\Class_\ClassConstantToSelfClassRector;
use Rector\Php80\Rector\FuncCall\ClassOnObjectRector;
use Rector\ValueObject\PhpVersion;

/*
 * Rector configuration for the GENERATED code tree (output/src, output/tests) only.
 *
 * This is deliberately separate from any rector config that might one day target the
 * generator's own `src/` - the risk profile, ruleset, and cadence for hand-written generator
 * code vs. machine-generated library code are different concerns.
 *
 * Goal here is final-pass cleanup/formatting of generated output, not structural rewrites.
 *
 * The ruleset below is a hand-picked, provably-safe subset rather than the full DEAD_CODE set
 * list (see php-fhir#198's "start lenient" guidance). Rector's dead-code set includes rules
 * (e.g. RemoveAlwaysTrueIfConditionRector, RemoveDeadInstanceOfRector, and the various
 * RemoveUnused*PropertyRector / RemoveUnused*MethodRector rules) that rely on PHPStan's inferred
 * types - including from docblocks. Running the full set against this repo's generated tree
 * surfaced a real bug: FHIRJSONSerializationOptions::_getJSONFieldElideSingletonArray() carried an
 * incorrect "@return true" docblock (now fixed), which caused PHPStan to infer an always-true
 * return type and Rector to delete a genuinely conditional call site - a correctness regression,
 * not a cleanup. Every rule enabled here is either purely syntactic (no type inference involved)
 * or docblock-only, so it cannot alter runtime behavior.
 *
 * Broader cleanups (unused private members, dead conditionals/branches, etc.) are valuable but
 * need to be evaluated and enabled individually, each verified the same way #195/#196/#197 were:
 * diff a full version-matrix generation, confirm phpunit + `php -l` parity, before merging.
 */
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/output/src',
        __DIR__ . '/output/tests',
    ]);

    // matches composer.json's "php": "^8.1" floor - never emit syntax newer than what generated
    // code is allowed to require.
    $rectorConfig->phpVersion(PhpVersion::PHP_81);

    $rectorConfig->rules([
        // --- modern idiom cleanup (php-fhir#197 backstop) ---
        // templates already emit the modern form directly, so this is a regression guard rather
        // than an expected source of diffs.
        ClassConstantToSelfClassRector::class,   // __CLASS__ -> self::class
        ClassOnObjectRector::class,              // get_class($x) -> $x::class

        // --- purely syntactic dead-code cleanup: no type inference involved ---
        RemoveDeadStmtRector::class,              // bare no-op expression statements
        RemoveDoubleAssignRector::class,          // "$x = 1; $x = 1;" duplicate assigns
        RemoveDoubleSelfAssignRector::class,      // "$x = $x;" no-op self assigns
        RemoveDuplicatedArrayKeyRector::class,    // duplicate literal array keys
        RemoveUnreachableStatementRector::class,  // code after return/throw/continue/break
        RemoveUselessReturnExprInConstructRector::class, // "return $x;" inside __construct
        SimplifyUselessVariableRector::class,     // "$x = f(); return $x;" -> "return f();"

        // --- docblock-only cleanup: comments only, zero runtime risk ---
        RemoveDuplicatedReturnSelfDocblockRector::class,
        RemoveMixedDocblockOverruledByNativeTypeRector::class,
        RemoveNonExistingVarAnnotationRector::class,
        RemoveNullTagValueNodeRector::class,
        RemoveReturnTagIncompatibleWithNativeTypeRector::class,
        RemoveUselessParamTagRector::class,
        RemoveUselessReturnTagRector::class,
        RemoveUselessUnionReturnDocblockRector::class,
        RemoveUselessVarTagRector::class,
        RemoveVoidDocblockFromMagicMethodRector::class,
    ]);

    // built-in unused "use" import removal (php-fhir#196), as a second layer of defense on top of
    // Templates::pruneUnusedImports(). Rector performs full AST-based analysis here, so it can
    // catch anything the lightweight regex heuristic in the generator missed.
    $rectorConfig->removeUnusedImports();

    // never touch the generator itself, only its output.
    $rectorConfig->skip([
        __DIR__ . '/src',
        __DIR__ . '/bin',
        __DIR__ . '/tests',
        __DIR__ . '/template',
    ]);
};
