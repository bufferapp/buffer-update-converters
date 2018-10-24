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

    public static function setRetweetData($status)
    {

        $text = self::getStatusText($status);
        $url = "https://twitter.com/" . $status->user->screen_name . "/status/" . $status->id;

        $retweet = [
            'user_id' => $status->user->id,
            'tweet_id' => $status->id_str,
            'username' => $status->user->screen_name,
            'url' => $url,
            'created_at' => strtotime($status->created_at),
            'created_at_string' => $status->created_at,
            'profile_name' => $status->user->name,
            'text' => $text,
            'avatars' => [
                'http' => $status->user->profile_image_url,
                'https' => $status->user->profile_image_url_https
            ]
        ];


        if (!is_null($status['comment'])) {
            $retweet['comment'] = $status['comment'];
        }

        return $retweet;
    }

    private function getStatistics($status)
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

    private function getStatusText($status)
    {
        return property_exists($status, 'full_text') ? $status->full_text : $status->text;
    }

    private function getServiceStatus($status)
    {
        return $this->isReply($status) ? 'service_reply' : 'service';
    }

    private function isReply($status)
    {
        $text = $this->getStatusText($status);
        return substr(html_entity_decode($text), 0, 1) == "@";
    }

}
