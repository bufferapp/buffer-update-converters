<?php

namespace Buffer\UpdateConverters;

use MongoDB\BSON\UTCDateTime;
use MongoDB\BSON\ObjectId;

class UpdateConverterInstagramTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function fromInstagramSingleImage()
    {
        $post = $this->makeInstagramPost();
        $update = UpdateConverterInstagram::convertFromSocialNetwork($post);
        $this->assertEquals($post['caption'], $update['text']);
        $this->assertEquals($post['media_url'], $update['media']['photo']);
        $this->assertEquals($post['media_url'], $update['media']['thumbnail']);
        $this->assertEquals(new UTCDateTime(strtotime($post['timestamp']) * 1000), $update['sent_at']);
        $this->assertEquals(new UTCDateTime(strtotime($post['timestamp']) * 1000), $update['due_at']);
        $this->assertEquals('instagram', $update['profile_service']);
        $this->assertEquals('instagram', $update['via']);
        $this->assertEquals('service', $update['status']);
    }

    /**
     * @test
     */
    public function fromInstagramCarousel()
    {
        $post = $this->makeInstagramCarousel();
        $update = UpdateConverterInstagram::convertFromSocialNetwork($post);
        $this->assertEquals($post['media_url'], $update['media']['photo']);
        $this->assertEquals($post['media_url'], $update['media']['thumbnail']);
        $this->assertEquals($post['children'][0], $update['extra_media'][0]['photo']);
        $this->assertEquals($post['children'][0], $update['extra_media'][0]['thumbnail']);
    }

    /**
     * @test
     */
    public function fromInstagramVideo()
    {
        $post = $this->makeInstagramVideo();
        $update = UpdateConverterInstagram::convertFromSocialNetwork($post);
        $video = $update['media']['video'];
        $this->assertEquals($post['media_url'], $video['details']['transcoded_location']);
        $this->assertEquals($post['media_url'], $update['media']['thumbnail']);
        $this->assertEquals($post['media_url'], $video['thumbnails'][0]);
    }

    // Helpers
    private function makeInstagramPost()
    {
        return [
            'id' => "1840054294297231246",
            'timestamp' => "2018-08-06T16:06:26+0000",
            'caption' => 'My worskpace today. I ditched the bigger screen setup I usually work with in exchange for a quieter mood for the deep work focus I need. I feel switching spaces is great for my creativity and focus! What do you do when you need a deeper focus?',
            'media_url' => 'https://scontent.xx.fbcdn.net/v/t51.2885-15/39059704_211947896342396_5652281763032989696_n.jpg?_nc_cat=108&_nc_ht=scontent.xx&oh=e263cc57e59cd952dfee1117d2e4a790&oe=5C44261D',
            'media_type' => 'IMAGE'
        ];
    }

    private function makeInstagramCarousel()
    {
        return [
            'id' => "1902404322505635765",
            'timestamp' => "2018-10-31T16:44:49+0000",
            'caption' => "A test carousel",
            'media_url' => "https://scontent.xx.fbcdn.net/v/t51.2885-15/42891484_324413344957919_212618408543834187_n.jpg?_nc_cat=104&_nc_ht=scontent.xx&oh=34ce9d91925e02c5673d21c038a3f69e&oe=5C717265",
            'media_type' => 'CAROUSEL_ALBUM',
            'children' => [
                "https://scontent.xx.fbcdn.net/v/t51.2885-15/43915071_2150249548520358_8891829936331534944_n.jpg?_nc_cat=106&_nc_ht=scontent.xx&oh=9dd4e9c456017ab190e54a2efcbba12f&oe=5C420EB5"
            ]
        ];
    }

    private function makeInstagramVideo()
    {
        $post = $this->makeInstagramPost();
        $post['media_type'] = 'VIDEO';

        return $post;
    }
}
