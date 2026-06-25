<?php

namespace Webkul\RovInspection\Http\Controllers;

use Aws\S3\S3Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;

/**
 * Signs browser -> S3 multipart uploads so very large inspection media
 * (multi-GB ROV footage) is streamed directly to S3 and never passes
 * through the PHP server.
 *
 * The client (see resources/views/forms/components/s3-multipart-upload.blade.php)
 * calls these endpoints to: create an upload, sign each part, then complete
 * (or abort) it. Only the final object key is persisted to the database.
 */
class S3MultipartUploadController extends Controller
{
    /** Keys may only ever live under this prefix. */
    private const KEY_PREFIX = 'rov-inspection/media/';

    /** Content types we allow to be uploaded. Mirrors the Filament field. */
    private const ALLOWED_MIME = [
        'video/mp4', 'video/webm', 'video/quicktime',
        'image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/jfif',
    ];

    private function client(): S3Client
    {
        $config = config('filesystems.disks.s3');

        $args = [
            'version'     => 'latest',
            'region'      => $config['region'],
            'credentials' => [
                'key'    => $config['key'],
                'secret' => $config['secret'],
            ],
        ];

        if (! empty($config['endpoint'])) {
            $args['endpoint'] = $config['endpoint'];
            $args['use_path_style_endpoint'] = (bool) ($config['use_path_style_endpoint'] ?? false);
        }

        return new S3Client($args);
    }

    private function bucket(): string
    {
        return config('filesystems.disks.s3.bucket');
    }

    /** Reject any key that is not within our managed prefix. */
    private function assertValidKey(string $key): void
    {
        abort_unless(
            $key !== '' && Str::startsWith($key, self::KEY_PREFIX) && ! Str::contains($key, '..'),
            422,
            'Invalid object key.'
        );
    }

    /** Step 1: open a multipart upload and hand back its id + generated key. */
    public function create(Request $request): JsonResponse
    {
        $data = $request->validate([
            'filename'     => ['required', 'string', 'max:255'],
            'content_type' => ['required', 'string', 'in:'.implode(',', self::ALLOWED_MIME)],
        ]);

        $extension = pathinfo($data['filename'], PATHINFO_EXTENSION);
        $extension = $extension ? '.'.Str::lower(preg_replace('/[^A-Za-z0-9]/', '', $extension)) : '';

        $key = self::KEY_PREFIX.now()->format('Y/m').'/'.Str::uuid()->toString().$extension;

        $result = $this->client()->createMultipartUpload([
            'Bucket'      => $this->bucket(),
            'Key'         => $key,
            'ContentType' => $data['content_type'],
        ]);

        return response()->json([
            'key'       => $key,
            'upload_id' => $result['UploadId'],
        ]);
    }

    /** Step 2 (per part): return a short-lived presigned URL for one part. */
    public function signPart(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key'         => ['required', 'string'],
            'upload_id'   => ['required', 'string'],
            'part_number' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);

        $this->assertValidKey($data['key']);

        $client = $this->client();

        $command = $client->getCommand('UploadPart', [
            'Bucket'     => $this->bucket(),
            'Key'        => $data['key'],
            'UploadId'   => $data['upload_id'],
            'PartNumber' => $data['part_number'],
        ]);

        $presigned = $client->createPresignedRequest($command, '+2 hours');

        return response()->json([
            'url' => (string) $presigned->getUri(),
        ]);
    }

    /** Step 3: assemble the uploaded parts into the final object. */
    public function complete(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key'             => ['required', 'string'],
            'upload_id'       => ['required', 'string'],
            'parts'           => ['required', 'array', 'min:1'],
            'parts.*.PartNumber' => ['required', 'integer', 'min:1'],
            'parts.*.ETag'    => ['required', 'string'],
        ]);

        $this->assertValidKey($data['key']);

        $parts = collect($data['parts'])
            ->sortBy('PartNumber')
            ->map(fn ($p) => ['PartNumber' => (int) $p['PartNumber'], 'ETag' => $p['ETag']])
            ->values()
            ->all();

        $result = $this->client()->completeMultipartUpload([
            'Bucket'          => $this->bucket(),
            'Key'             => $data['key'],
            'UploadId'        => $data['upload_id'],
            'MultipartUpload' => ['Parts' => $parts],
        ]);

        return response()->json([
            'key'      => $data['key'],
            'location' => $result['Location'] ?? null,
        ]);
    }

    /** Cleanup: discard a failed/cancelled upload so partial data is not billed. */
    public function abort(Request $request): JsonResponse
    {
        $data = $request->validate([
            'key'       => ['required', 'string'],
            'upload_id' => ['required', 'string'],
        ]);

        $this->assertValidKey($data['key']);

        $this->client()->abortMultipartUpload([
            'Bucket'   => $this->bucket(),
            'Key'      => $data['key'],
            'UploadId' => $data['upload_id'],
        ]);

        return response()->json(['ok' => true]);
    }
}
