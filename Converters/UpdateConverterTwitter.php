<?php

namespace Buffer\UpdateConverters;

use MongoDB\BSON\UTCDateTime;

class UpdateConverterTwitter implements NativeUpdateConverter
{
    public static function convertFromSocialNetwork($status)
    {
        $text = self::getStatusText($status);

        $updateAttrs = [
            'service_update_id' => $status->id_str,
            'due_at' => new UTCDateTime(1000 * strtotime($status->created_at)),
            'sent_at' => new UTCDateTime(1000 * strtotime($status->created_at)),
            'profile_service' => 'twitter',
            'status' => self::getServiceStatus($status),
            'text' => html_entity_decode($text),
            'via' => 'twitter',
            'statistics' => self::getStatistics($status)
        ];

        // Check for photos
        if (isset($status->entities) && isset($status->entities->media)) {
            foreach ($status->entities->media as $medium) {
                if ($medium->type == "photo") {
                    $updateAttrs['media'] = array(
                        'photo' => $medium->media_url_https,
                        'thumbnail' => $medium->media_url_https . ':thumb'
                    );
                }
            }
        }

        return $updateAttrs;
    }

    public static function setRetweetData($retweetedStatus)
    {

        $text = self::getStatusText($retweetedStatus);
        $url = "https://twitter.com/" . $retweetedStatus->user->screen_name . "/status/" . $retweetedStatus->id;

        $retweet = [
            'user_id' => $retweetedStatus->user->id,
            'tweet_id' => $retweetedStatus->id_str,
            'username' => $retweetedStatus->user->screen_name,
            'url' => $url,
            'created_at' => strtotime($retweetedStatus->created_at),
            'created_at_string' => $retweetedStatus->created_at,
            'profile_name' => $retweetedStatus->user->name,
            'text' => $text,
            'avatars' => [
                'http' => $retweetedStatus->user->profile_image_url,
                'https' => $retweetedStatus->user->profile_image_url_https
            ]
        ];


        if (!empty($retweetedStatus->comment)) {
            $retweet['comment'] = $retweetedStatus->comment;
        }

        return $retweet;
    }

    private static function getStatistics($status)
    {
        $recentStatus = strtotime($status->created_at) > (time() - (60 * 60 * 24));

        if (self::isReply($status) || !$recentStatus) {
            return [
                'retweets' => isset($status->retweet_count) ? $status->retweet_count : 0,
                'favorites' => isset($status->favorite_count) ? $status->favorite_count : 0,
                'mentions' => 0
            ];
        };

        return [
            'reach' => (int) isset($status->user) ? $status->user->followers_count : 0,
            'retweets' => isset($status->retweet_count) ? $status->retweet_count : 0,
            'favorites' => isset($status->favorite_count) ? $status->favorite_count : 0,
            'mentions' => 0
        ];
    }

    private static function getStatusText($status)
    {
        return property_exists($status, 'full_text') ? $status->full_text : $status->text;
    }

    private static function getServiceStatus($status)
    {
        return self::isReply($status) ? 'service_reply' : 'service';
    }

    private static function isReply($status)
    {
        $text = self::getStatusText($status);
        return substr(html_entity_decode($text), 0, 1) == "@";
    }
}
