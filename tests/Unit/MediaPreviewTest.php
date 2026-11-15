<?php

namespace Tests\Unit;

use App\Media\MediaPreview;
use PHPUnit\Framework\TestCase;

class MediaPreviewTest extends TestCase
{
    public function test_it_builds_preview_data_for_images_and_non_image_media(): void
    {
        $media = [
            new class
            {
                public string $name = 'foto';

                public string $mime_type = 'image/jpeg';

                public function getUrl(): string
                {
                    return 'https://example.test/foto.jpg';
                }
            },
            new class
            {
                public string $name = 'handleiding';

                public string $mime_type = 'application/pdf';

                public function getUrl(): string
                {
                    return 'https://example.test/handleiding.pdf';
                }
            },
        ];

        self::assertSame([
            [
                'imgSrc' => 'https://example.test/foto.jpg',
                'imgAlt' => 'foto',
                'isImage' => true,
            ],
            [
                'imgSrc' => 'https://example.test/handleiding.pdf',
                'imgAlt' => 'handleiding',
                'isImage' => false,
            ],
        ], (new MediaPreview)->from($media));
    }
}
