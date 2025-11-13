<?php

namespace Antmin\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LogRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    # 尝试次数
    public int $tries = 3;
    # 最大超时
    public int $timeout = 10;

    protected array $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function handle()
    {

    }



}

