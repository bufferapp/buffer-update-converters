<?php

namespace Buffer\UpdateConverters;

use MongoDB\BSON\UTCDateTime;

class UpdateConverterTwitterTest extends \PHPUnit_Framework_TestCase
{
    public function testFromTwitterStatus()
    {
        $twitterStatus = $this->makeTwitterStatus();
        $update = UpdateConverterTwitter::convertFromSocialNetwork($twitterStatus);
        // var_dump($update); exit;
        $this->assertEquals('12345', $update['service_update_id']);
        $this->assertEquals(new UTCDateTime(1000 * strtotime($twitterStatus->created_at)), $update['due_at']);
        $this->assertEquals(new UTCDateTime(1000 * strtotime($twitterStatus->created_at)), $update['sent_at']);
        $this->assertEquals('twitter', $update['profile_service']);
        $this->assertEquals('twitter', $update['via']);
        $this->assertEquals([
            'retweets' => 5,
            'favorites' => 10,
            'mentions' => 0,
        ], $update['statistics']);
    }

    public function testFromTwitterStatusHasCorrectStatus()
    {
        $serviceUpdate = UpdateConverterTwitter::convertFromSocialNetwork($this->makeTwitterStatus());

        $serviceReplyUpdate = UpdateConverterTwitter::convertFromSocialNetwork(
            $this->makeTwitterStatus(['text' => '@someone just replying'])
        );
        $this->assertEquals($serviceUpdate['status'], 'service');
        $this->assertEquals($serviceReplyUpdate['status'], 'service_reply');
    }

    public function testFromTwitterStatusImages()
    {
        $picUrl = 'https://pbs.twimg.com/media/Bnr1DieIEAAdITy.png';

        $overrides = [
            'entities' => [
                'media' => [
                    [
                        'type' => 'photo',
                        'media_url_https' => $picUrl
                    ]
                ]
            ]
        ];

        $update = UpdateConverterTwitter::convertFromSocialNetwork($this->makeTwitterStatus($overrides));

        $this->assertEquals($picUrl, $update['media']['photo']);
        $this->assertEquals($picUrl . ':thumb', $update['media']['thumbnail']);
    }

    public function testSetRetweetData()
    {
        $overrides = [
            'retweeted_status' => [
                'id' => 470571408896962560,
                'id_str' => '470571408896962560',
                'created_at' => 'Tue May 27 16:53:03 +0000 2014',
                'text' => 'just another tweet',
                "user" => [
                    "id" => 425938818,
                    "id_str" => "425938818",
                    'screen_name' => 'somedude',
                    'name' => 'twitter name',
                    'profile_image_url' => 'http://pbs.twimg.com/profile_images/378800000359425267/e3896bf5350d36c6c5f4ce47dfd4f718_normal.jpeg',
                    'profile_image_url_https' => 'https://pbs.twimg.com/profile_images/378800000359425267/e3896bf5350d36c6c5f4ce47dfd4f718_normal.jpeg'
                ],
            ]
        ];
        $status = $this->makeTwitterStatus($overrides);
        $retweet = UpdateConverterTwitter::setRetweetData($status->retweeted_status);


        $this->assertEquals('470571408896962560', $retweet['tweet_id']);
        $this->assertEquals(425938818, $retweet['user_id']);
        $this->assertEquals('somedude', $retweet['username']);
        $this->assertEquals('twitter name', $retweet['profile_name']);
        $this->assertEquals(
            'http://pbs.twimg.com/profile_images/378800000359425267/e3896bf5350d36c6c5f4ce47dfd4f718_normal.jpeg',
            $retweet['avatars']['http']
        );
        $this->assertEquals(
            "https://twitter.com/somedude/status/470571408896962560",
            $retweet['url']
        );
    }

    // Helpers
    private function makeTwitterStatus($overrides = [])
    {
        return json_decode(json_encode(array_replace([
            'id_str' => '12345',
            'created_at' => 'Sat May 24 05:49:07 +0000 2014',
            'text' => 'Just a normal tweet',
            'source' => '<a href="http://twitter.com/download/iphone" rel="nofollow">Twitter for iPhone</a>',
            'user' => [
                'followers_count' => 100,

            ],
            'retweet_count' => 5,
            'favorite_count' => 10,

        ], $overrides)));
    }
}
