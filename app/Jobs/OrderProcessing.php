<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OrderProcessing implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var
     */
    protected $order;

    /**
     * Create a new job instance.
     *
     * @param $order
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $text = $this->convertBengaliToUnicode('আপনার অর্ডারটি (অর্ডার নংঃ ' . $this->order['order_no'] . ') সফল ভাবে সম্পন্ন হয়েছে। দয়া করে যোগাযোগ করুন: (ডিলারের নাম: ' . $this->order['dealer_name'] . '), (ডিলারের ফোন: ' . $this->order['dealer_mobile'] . ')।');

            $res = Http::get(config('helper.ssl_sms_endpoint'), [
                'user' => config('helper.ssl_sms_username'),
                'pass' => config('helper.ssl_sms_password'),
                'sid' => config('helper.ssl_sms_sid'),
                'sms' => $text,
                'msisdn' => $this->order['mobile'],
                'csmsid' => $this->order['id'],
            ]);
            if (config('helper.ssl_sms_is_localhost')) {
                Log::channel('sms')->info('ORDER PROCESSING: ' . $res->body());
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
        return strtoupper(bin2hex(iconv('UTF-8', 'UCS-2BE', $text)));
    }
}
