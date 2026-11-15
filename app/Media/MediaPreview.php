<?php

namespace App\Media;

final class MediaPreview
{
    /**
     * @param  iterable<object>  $mediaItems
     * @return list<array{imgSrc: string, imgAlt: string, isImage: bool}>
     */
    public function from(iterable $mediaItems): array
    {
        $preview = [];

        foreach ($mediaItems as $media) {
            $preview[] = [
                'imgSrc' => $media->getUrl(),
                'imgAlt' => (string) $media->name,
                'isImage' => str_starts_with((string) $media->mime_type, 'image/'),
            ];
        }

        return $preview;
    }
}
