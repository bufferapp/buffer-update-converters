<?php

namespace Buffer\UpdateConverters;

interface NativeUpdateConverter
{
    /**
     * Instatiates a new update from a social network status and returns it
     *
     * @param $status
     * @return Update
     */
    public static function convertFromSocialNetwork($status);
}