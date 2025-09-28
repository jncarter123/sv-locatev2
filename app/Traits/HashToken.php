<?php

namespace App\Traits;

trait HashToken
{
    public function hashToken(string $token): string
    {
        return sha1($token);
    }
}
