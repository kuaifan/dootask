<?php

namespace Tests\Unit;

use App\Module\Manticore\ManticoreFile;
use FFI\Exception as FFIException;
use ReflectionClass;
use Tests\TestCase;

/**
 * Regression test for the /api/file/fetch path traversal in
 * App\Module\Manticore\ManticoreFile::extractFromPath().
 *
 * extractFromPath() is the chokepoint both extractFileContentFromPath()
 * (the direct-path caller, reachable from the public API via
 * FileController::fetch()) and extractFileContent() (its URL-based
 * sibling) funnel through. Before the fix it joined public_path() with
 * the caller-supplied relative path and read it with no directory
 * confinement check beyond a literal 'uploads/' string prefix earlier in
 * the chain, which "../" segments trivially satisfy while still escaping
 * the uploads directory.
 *
 * This environment cannot run the extraction all the way through
 * TextExtractor::extractFile(), because every response built via
 * Base::retSuccess()/retError() calls Doo::translate(), which loads a
 * proprietary FFI binary (/usr/lib/doo/doo.so) present in the project's
 * real Docker image but not in a bare checkout. That gives this test its
 * differential signal instead of blocking it: pre-fix, extractFromPath()
 * proceeds past the (missing) confinement check and reaches
 * TextExtractor::extractFile(), which throws the FFI\Exception. Post-fix,
 * the new realpath-based confinement check rejects the traversal path
 * and returns an error array before ever reaching TextExtractor, so no
 * exception is thrown.
 */
class ManticoreFilePathTraversalTest extends TestCase
{
    public function test_traversal_path_is_rejected_before_reaching_the_extractor(): void
    {
        $attackerPath = 'uploads/../../.env';

        $method = (new ReflectionClass(ManticoreFile::class))->getMethod('extractFromPath');
        $method->setAccessible(true);

        try {
            $result = $method->invoke(null, $attackerPath);
        } catch (FFIException $e) {
            $this->fail(
                'extractFromPath() proceeded past its confinement check and reached '
                . 'TextExtractor::extractFile() for a path outside uploads/ (' . $attackerPath . '). '
                . 'FFI exception (expected only for a path that should have been rejected first): '
                . $e->getMessage()
            );
        }

        $this->assertIsArray($result);
        $this->assertArrayHasKey('error', $result);
    }
}
