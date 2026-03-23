<?php

namespace App\Queue\Jobs;

interface Job
{
    public function getKey(): string;
    public function getPayload(): string;
}
