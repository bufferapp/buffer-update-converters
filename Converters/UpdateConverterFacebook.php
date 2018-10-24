<?php

namespace Buffer\UpdateConverters;

use MongoDB\BSON\UTCDateTime;

class UpdateConverterFacebook implements NativeUpdateConverter
{
    public static function convertFromSocialNetwork($status)
    {
        if (!array_key_exists('id', $status) || !array_key_exists('created_time', $status)) {
            return null;
        }

        if (array_key_exists('message', $status) && $status['message'] != '') {
            $text = $status['message'];
        } elseif (array_key_exists('name', $status)) {
            $text = $status['name'];
        } elseif (array_key_exists('description', $status)) {
            $text = $status['description'];
        } else {
            $text = null;
        }

        $update_attrs = array(
            'service_update_id' => $status['id'],
            'text' => html_entity_decode($text),
            'due_at' => new UTCDateTime(1000*strtotime($status['created_time'])),
            'sent_at' => new UTCDateTime(1000*strtotime($status['created_time'])),
            'profile_service' => 'facebook',
            'status' => 'service',
            'via' => 'facebook',
        );

        //check for images
        $has_media = isset($status['attachments']['data']);

        if ($has_media) {
            $has_one_media_item = isset($status['attachments']['data'][0]['media']);
            $has_multiple_media_items = isset($status['attachments']['data'][0]['subattachments']);
            if ($has_one_media_item) {
                $attachment = $status['attachments']['data'][0];
                $update_attrs['media'] = self::getMedia($attachment, $status);
            } elseif ($has_multiple_media_items) {
                $attachments = $status['attachments']['data'][0]['subattachments']['data'];
                $maybeNullMedias = array_map(function ($attachment) {
                    return self::getMedia($attachment);
                }, $attachments);
                $nonNullMedias = array_filter($maybeNullMedias, function ($media) {
                    return !empty($media);
                });
                $update_attrs['media'] = array_shift($nonNullMedias);
                $update_attrs['extra_media'] = $nonNullMedias;
            }
        }

        //If there is an attachments name and no message, set text to the name
        if ($update_attrs['text'] == '' && isset($status['attachments']['name'])) {
            $update_attrs['text'] = $status['attachments']['name'];
        }

        return $update_attrs;
    }

    private static function getMedia($attachment, $status = [])
    {
        $media = null;
        $is_link = isset($attachment['type']) && $attachment['type'] === 'share';
        $is_video = isset($attachment['type']) && $attachment['type'] === 'video_inline' && isset($status['source']);
        if ($is_link) {
            $media = self::getLink($attachment);
        } elseif ($is_video) {
            $media = [
                'video' => [
                    'details' => [
                        'transcoded_location' => $status['source'],
                        'location' => $status['source'],
                        'width' => $attachment['media']['image']['width'],
                        'height' => $attachment['media']['image']['height']
                    ],
                    'thumbnails' => [$attachment['media']['image']['src']]
                ],
                'thumbnail' => $attachment['media']['image']['src']
            ];
        } elseif (isset($attachment['media'])) {
            $media = [
                'photo' => $attachment['media']['image']['src'],
                'thumbnail' => $attachment['media']['image']['src']
            ];
        }

        return $media;
    }

    private static function getLink($attachment)
    {
        if (isset($attachment['target'])) {
            $link = $attachment['target']['url'];
        } elseif (isset($attachment['url'])) {
            $link = $attachment['url'];
        } else {
            $link = null;
        }

        $description = isset($attachment['description']) ? $attachment['description'] : null;
        return [
            'link' => $link,
            'title' => isset($attachment['title']) ? $attachment['title'] : null,
            'description' => $description,
            'thumbnail' => $attachment['media']['image']['src'],
            'picture' => $attachment['media']['image']['src'],
        ];
    }
}
