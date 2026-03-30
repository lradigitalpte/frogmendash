<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class TestS3Connection extends Command
{
    protected $signature = 'test:s3';
    protected $description = 'Test S3 connection and bucket access';

    public function handle()
    {
        $this->info('🔍 Testing S3 Connection...');
        $this->newLine();

        try {
            // Test 1: List bucket contents
            $this->info('1️⃣  Attempting to list bucket contents...');
            $files = Storage::disk('s3')->listContents('/');
            $this->info('✅ Successfully connected to S3 bucket!');
            $fileCount = iterator_count($files);
            $this->info("   Files in bucket: " . $fileCount);
            $this->newLine();

            // Test 2: Create a test file
            $this->info('2️⃣  Attempting to upload a test file...');
            $testFileName = 'test-' . now()->timestamp . '.txt';
            Storage::disk('s3')->put($testFileName, 'Test file created at ' . now());
            $this->info("✅ Successfully uploaded: {$testFileName}");
            $this->newLine();

            // Test 3: Read the test file
            $this->info('3️⃣  Attempting to read the test file...');
            $content = Storage::disk('s3')->get($testFileName);
            $this->info("✅ Successfully read file content: {$content}");
            $this->newLine();

            // Test 4: Delete the test file
            $this->info('4️⃣  Attempting to delete the test file...');
            Storage::disk('s3')->delete($testFileName);
            $this->info("✅ Successfully deleted: {$testFileName}");
            $this->newLine();

            $this->info('✅ All S3 tests passed! Connection is working perfectly.');

        } catch (\Exception $e) {
            $this->error('❌ S3 Connection Test Failed!');
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
