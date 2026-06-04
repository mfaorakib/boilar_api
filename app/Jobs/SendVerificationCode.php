<?php

namespace App\Jobs;

use App\Entities\User\MessageCode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendVerificationCode implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var MessageCode
     */
    protected $code;

    /**
     * Create a new job instance.
     *
     * @param MessageCode $code
     */
    public function __construct(MessageCode $code)
    {
        $this->code = $code;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $text = $this->convertBengaliToUnicode('প্রিয় গ্রাহক, আপনার এককালীন পাসওয়ার্ডটি (OTP) হচ্ছে ' . $this->code->code . ' অনুগ্রহ করে ২.৫ মিনিটের মধ্যে এককালীন পাসওয়ার্ডটি ব্যবহার করুন।');

            $res = Http::get(config('helper.ssl_sms_endpoint'), [
                'user' => config('helper.ssl_sms_username'),
                'pass' => config('helper.ssl_sms_password'),
                'sid' => config('helper.ssl_sms_sid'),
                'sms' => $text,
                'msisdn' => $this->code->mobile,
                'csmsid' => $this->code->id,
            ]);
            if (config('helper.ssl_sms_is_localhost')) {
                Log::channel('sms')->info('OTP: ' . $res->body());
            }

        } catch (\Throwable $exception) {
            report($exception);
        }
    }

    /**
     * @param $text
     * @return string
     */
    private function convertBengaliToUnicode($text)
    {
        return mb_strtoupper(bin2hex(iconv('UTF-8', 'UCS-2BE', $text)));
    }
}
