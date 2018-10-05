<?php

namespace Buffer\UpdateConverters;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

class UpdateConverterFacebookTest extends \PHPUnit_Framework_TestCase
{
    public function testFromFacebookStatus()
    {
        $fb_status = $this->makeFacebookStatus();
        $update = UpdateConverterFacebook::convertFromSocialNetwork($fb_status);

        $this->assertEquals('777687525604678_777698725603558', $update['service_update_id']);
        $this->assertEquals($fb_status['message'], $update['text']);
        $this->assertEquals(new UTCDateTime(1000*strtotime(1401970981)), $update['sent_at']);
        $this->assertEquals(new UTCDateTime(1000*strtotime(1401970981)), $update['due_at']);
        $this->assertEquals('facebook', $update['profile_service']);
        $this->assertEquals('facebook', $update['via']);
        $this->assertEquals('service', $update['status']);
    }

    public function testFromFacebookStatusImages()
    {
        $media_json = [
            "attachments" => [
                'data' => [
                    [
                        'media' => [
                            'image' => [
                                'src'=> 'https://fbcdn-photos-e-a.akamaihd.net/hphotos-ak-xpf1/t1.0-0/10446483_778288288877935_8375407983187109054_s.jpg',
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $fb_status = $this->makeFacebookStatus($media_json);
        $update = UpdateConverterFacebook::convertFromSocialNetwork($fb_status);
        $this->assertNotNull($update['media']);
        $this->assertEquals($fb_status['attachments']['data'][0]['media']['image']['src'], $update['media']['photo']);
        $this->assertEquals($fb_status['attachments']['data'][0]['media']['image']['src'], $update['media']['thumbnail']);
    }

    /**
     * @test
     */
    public function fromFacebookGallery()
    {
        $media_json = [
            "attachments" => [
                'data' => [
                    [
                        'subattachments' => [
                            'data' => [
                                [
                                    'media' => [
                                        'image' => [
                                            'src'=> 'https://fbcdn-photos-e-a.akamaihd.net/hphotos-ak-xpf1/t1.0-0/10446483_778288288877935_8375407983187109054_s.jpg',
                                        ]
                                    ]
                                ],
                                [
                                    'media' => [
                                        'image' => [
                                            'src'=> 'https://fbcdn-photos-e-a.akamaihd.net/hphotos-ak-xpf1/t1.0-0/10446483_778288288877935_8375407983187109054_s.jpg',
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $fb_status = $this->makeFacebookStatus($media_json);
        $update = UpdateConverterFacebook::convertFromSocialNetwork($fb_status);
        $this->assertNotNull($update['media']);
        $this->assertEquals($fb_status['attachments']['data'][0]['subattachments']['data'][0]['media']['image']['src'], $update['media']['photo']);
        $this->assertEquals($fb_status['attachments']['data'][0]['subattachments']['data'][0]['media']['image']['src'], $update['media']['thumbnail']);
        $this->assertEquals($fb_status['attachments']['data'][0]['subattachments']['data'][1]['media']['image']['src'], $update['extra_media'][0]['photo']);
        $this->assertEquals($fb_status['attachments']['data'][0]['subattachments']['data'][1]['media']['image']['src'], $update['extra_media'][0]['thumbnail']);
        $this->assertCount(1, $update['extra_media']);
    }

    public function testFromFacebookStatusUseDescriptionIfNoMessage()
    {
        $overrides = array('message' => '', 'description' => 'foo bar!');
        $fb_status = $this->makeFacebookStatus($overrides);

        $update = UpdateConverterFacebook::convertFromSocialNetwork($fb_status);
        $this->assertEquals('foo bar!', $update['text']);
    }

    /**
     * @test
     */
    public function fromFacebookLinkAttachment()
    {
        $media_json = [
            "attachments" => [
                "data" => [
                    [
                        "type" => "share",
                        "target" => [
                            "url" => "http://buffer.com"
                        ],
                        "title" => "Buffer",
                        "description" => "A better way to share on social media.",
                        "media" => [
                            "image" => [
                                "src" => "image_url"
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $fb_status = $this->makeFacebookStatus($media_json);
        $update = UpdateConverterFacebook::convertFromSocialNetwork($fb_status);
        $this->assertEquals("Buffer", $update['media']['title']);
    }

    // Helpers
    private function makeFacebookStatus($overrides = array())
    {
        return array_replace(array(
            'id' => "777687525604678_777698725603558",
            'created_time' => 1401970981,
            'message' => 'Working hard on my next novel! Building a nice character sketch for my protagonist!',
            'description' => '',
            'attachment' => array('description' => ''),
            'impressions' => null,
            'like_info' => array('like_count' => 4),
            'comment_info' => array( 'comment_count' => 1),
            'share_count' => 0
        ), $overrides);
    }
}
