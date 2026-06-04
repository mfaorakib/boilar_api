<?php

namespace App\Jobs;

use App\Notifications\PostCreated;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class SendPushNotification
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var
     */
    protected $post;

    /**
     * Create a new notification instance.
     *
     * @param $post
     * @return void
     */
    public function __construct($post)
    {
        $this->post = $post;
    }


    /**
     * Send push notification.
     */
    public function handle()
    {
        try {

            auth()->user()->notify(new PostCreated($this->post));

        } catch (\Exception $exception) {
            report($exception);
        }
    }
}
