<?php

namespace App\Console\Commands;

use App\Services\PostService;
use Illuminate\Console\Command;
use Illuminate\Http\Request;

class GetPostSchedule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:get-post-schedule';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get five posts from celebrities on Instagram every day';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Fetching posts");
        $celebs = [
            "falzthebahdguy",
            "adekunlegold",
            "symplysimi",
            "kimkardashian",
            "realmercyaigbe"
        ];
        $message = (new PostService)->saveMultiplePosts($celebs);
        $this->info($message['message']);
    }
}
