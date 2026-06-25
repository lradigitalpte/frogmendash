<?php

namespace Webkul\RovInspection\Filament\Forms\Components;

use Filament\Forms\Components\Field;

/**
 * A file field that uploads directly to S3 via multipart presigned URLs,
 * so multi-GB files never pass through the PHP server. The field's state
 * is simply the resulting S3 object key (string).
 *
 * @see \Webkul\RovInspection\Http\Controllers\S3MultipartUploadController
 */
class S3MultipartUpload extends Field
{
    protected string $view = 'rov-inspection::forms.components.s3-multipart-upload';

    /** @var array<int, string> */
    protected array $acceptedFileTypes = [];

    /** Max size in KB (null = no client-side cap). */
    protected ?int $maxSizeKb = null;

    /**
     * @param  array<int, string>  $types
     */
    public function acceptedFileTypes(array $types): static
    {
        $this->acceptedFileTypes = $types;

        return $this;
    }

    public function maxSize(int $kilobytes): static
    {
        $this->maxSizeKb = $kilobytes;

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getAcceptedFileTypes(): array
    {
        return $this->acceptedFileTypes;
    }

    /** Client-side cap in bytes. */
    public function getMaxSizeBytes(): ?int
    {
        return $this->maxSizeKb !== null ? $this->maxSizeKb * 1024 : null;
    }
}
