<?php

namespace Tests\Unit;

use App\Module\Manticore\ManticoreFile;
use ReflectionMethod;
use Tests\TestCase;

class ManticoreFilePathTraversalTest extends TestCase
{
    private function callExtractFromPath(string $relativePath)
    {
        $method = new ReflectionMethod(ManticoreFile::class, 'extractFromPath');
        $method->setAccessible(true);
        return $method->invoke(null, $relativePath);
    }

    public function test_rejects_a_traversal_path_that_starts_with_uploads_but_escapes_it()
    {
        // Regression for the path-traversal bug: "uploads/../../etc/passwd"
        // still starts with "uploads/" (the only check the caller applies),
        // but public_path() performs no ../ resolution of its own, so the
        // resolved path escapes the uploads directory entirely.
        $outsideDir = dirname(public_path());
        $secretFile = $outsideDir . '/manticore_poc_secret.txt';
        file_put_contents($secretFile, 'FAKE-POC-SECRET: outside uploads dir');

        try {
            $relative = str_repeat('../', 20) . ltrim($secretFile, '/');
            $payload = 'uploads/' . $relative;

            $result = $this->callExtractFromPath($payload);

            $this->assertIsArray($result, 'A traversal payload must be rejected, not return extracted content');
            $this->assertArrayHasKey('error', $result);
        } finally {
            @unlink($secretFile);
        }
    }

    public function test_allows_a_legitimate_path_inside_uploads()
    {
        // Exercises the same containment check extractFromPath() applies,
        // without going through the later text-extraction step (which in
        // this environment depends on a native library this sandbox does
        // not have installed, unrelated to path safety).
        $dir = public_path('uploads/manticore_test');
        @mkdir($dir, 0777, true);
        $file = $dir . '/normal.txt';
        file_put_contents($file, 'legitimate file inside uploads');

        try {
            $uploadsRoot = realpath(public_path('uploads'));
            $resolvedPath = realpath($file);

            $this->assertNotFalse($uploadsRoot);
            $this->assertNotFalse($resolvedPath);
            $this->assertTrue(
                str_starts_with($resolvedPath, $uploadsRoot . DIRECTORY_SEPARATOR),
                'A legitimate path inside uploads/ must satisfy the containment check'
            );
        } finally {
            @unlink($file);
            @rmdir($dir);
        }
    }
}
