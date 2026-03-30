<?php

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

echo "🔍 Testing S3 Connection...\n\n";

try {
    // Create S3 client
    $s3Client = new S3Client([
        'version' => 'latest',
        'region' => env('AWS_DEFAULT_REGION', 'eu-north-1'),
        'credentials' => [
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
        ],
    ]);

    echo "1️⃣  Testing S3 client creation and credentials...\n";
    
    // Test bucket access
    $bucket = env('AWS_BUCKET', 'frogmen');
    echo "2️⃣  Attempting to list objects in bucket: {$bucket}...\n";
    
    $result = $s3Client->listObjectsV2(['Bucket' => $bucket]);
    
    if (isset($result['Contents'])) {
        $count = count($result['Contents']);
        echo "✅ Successfully connected! Found {$count} objects in bucket.\n\n";
    } else {
        echo "✅ Successfully connected! Bucket is empty.\n\n";
    }

    // Test upload
    echo "3️⃣  Testing file upload...\n";
    $testKey = 'test-' . time() . '.txt';
    $testContent = 'Hello from FrogMen Dashboard at ' . date('Y-m-d H:i:s');
    
    $s3Client->putObject([
        'Bucket' => $bucket,
        'Key' => $testKey,
        'Body' => $testContent,
        'ContentType' => 'text/plain',
    ]);
    
    echo "✅ Successfully uploaded: {$testKey}\n\n";

    // Test download
    echo "4️⃣  Testing file download...\n";
    $result = $s3Client->getObject([
        'Bucket' => $bucket,
        'Key' => $testKey,
    ]);
    
    $downloadedContent = (string) $result['Body'];
    echo "✅ Successfully downloaded content: {$downloadedContent}\n\n";

    // Test delete
    echo "5️⃣  Cleaning up test file...\n";
    $s3Client->deleteObject([
        'Bucket' => $bucket,
        'Key' => $testKey,
    ]);
    
    echo "✅ Successfully deleted: {$testKey}\n\n";
    
    echo "🎉 All S3 tests passed! AWS credentials are working perfectly.\n";
    echo "Region: " . env('AWS_DEFAULT_REGION') . "\n";
    echo "Bucket: " . env('AWS_BUCKET') . "\n";

} catch (AwsException $e) {
    echo "❌ AWS Error: " . $e->getMessage() . "\n";
    echo "Error Code: " . $e->getAwsErrorCode() . "\n";
    echo "HTTP Status: " . $e->getStatusCode() . "\n";
} catch (Exception $e) {
    echo "❌ General Error: " . $e->getMessage() . "\n";
}