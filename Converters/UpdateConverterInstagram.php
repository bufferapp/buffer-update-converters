<?php

namespace Buffer\UpdateConverters;

use MongoDB\BSON\UTCDateTime;

class UpdateConverterInstagram implements NativeUpdateConverter
{
    public static function convertFromSocialNetwork($post)
    {
        $update_attrs = [
            'service_update_id' => $post['ig_id'],
            'text' => html_entity_decode($post['caption']),
            'due_at' => new UTCDateTime(1000*strtotime($post['timestamp'])),
            'sent_at' => new UTCDateTime(1000*strtotime($post['timestamp'])),
            'profile_service' => 'instagram',
            'status' => 'service',
            'via' => 'instagram',
        ];

        if ($post['media_type'] === 'VIDEO') {
            $update_attrs['media'] = [
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
            $update_attrs['media'] = [
                'photo' => $post['media_url'],
                'thumbnail' => $post['media_url'],
            ];
        }

        return $update_attrs;
    }
}
