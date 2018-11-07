<?php

namespace Buffer\UpdateConverters;

use MongoDB\BSON\UTCDateTime;

class UpdateConverterInstagram implements NativeUpdateConverter
{
    public static function convertFromSocialNetwork($post)
    {
        $updateAttrs = [
            'service_update_id' => $post['id'],
            'text' => html_entity_decode($post['caption']),
            'due_at' => new UTCDateTime(1000*strtotime($post['timestamp'])),
            'sent_at' => new UTCDateTime(1000*strtotime($post['timestamp'])),
            'profile_service' => 'instagram',
            'status' => 'service',
            'via' => 'instagram',
        ];

        if ($post['media_type'] === 'VIDEO') {
            $updateAttrs['media'] = [
                'video' => [
                    'details' => [
                        'transcoded_location' => $post['media_url']
                    ],
                    'thumbnails' => [
                        $post['media_url']
                    ]
                ],
                'thumbnail' => $post['media_url']
            ];
        } else {
            $updateAttrs['media'] = [
                'photo' => $post['media_url'],
                'thumbnail' => $post['media_url'],
            ];
            if ($post['media_type'] === 'CAROUSEL_ALBUM') {
                foreach ($post['children'] as $media) {
                    $extraMedia = [
                        'photo' => $media,
                        'thumbnail' => $media
                    ];
                    $updateAttrs['extra_media'][] = $extraMedia;
                }
            }
        }

        return $updateAttrs;
    }
}
