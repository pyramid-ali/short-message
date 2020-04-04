<?php


namespace Alish\ShortMessage\Drivers;


use Alish\ShortMessage\Contracts\ShortMessage;
use Alish\ShortMessage\Messages\GhasedakOtp;
use Alish\ShortMessage\SentFail;
use Alish\ShortMessage\SentSuccessful;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Ghasedak implements ShortMessage
{

    protected $config;

    protected $baseUrl = 'https://api.ghasedak.io/v2';

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function send(array $recipients, string $message)
    {
        $payload = [
            'message' => $message,
            'receptor' => implode(',', $recipients)
        ];

        $response = Http::withHeaders([
            'apikey' => $this->apiKey()
        ])
            ->asForm()
            ->post($this->endpoint('sms/send/pair'), $payload);

        if ($response->successful() && $response['result']['code'] === 200) {
            return new SentSuccessful($response['items']);
        }

        return new SentFail($response->json());
    }

    public function otp(array $recipients, GhasedakOtp $otp)
    {
        $payload = [
            'receptor' => implode(',', $recipients),
            'type' => $otp->type,
            'template' => $otp->template
        ];

        foreach ($otp->params as $index => $param) {
            $payload['param' . ($index + 1)] = $param;
        }

        if (!is_null($otp->checkId)) {
            $payload['checkid'] = $otp->checkId;
        }

        $response = Http::withHeaders([
            'apikey' => $this->apiKey()
        ])
            ->asForm()
            ->post($this->endpoint('verification/send/simple'), $payload);

        if ($response->successful() && $response['result']['code'] === 200) {
            return new SentSuccessful($response['items']);
        }

        return new SentFail($response->json());
    }

    protected function apiKey()
    {
        return $this->config['api-key'];
    }

    protected function endpoint($url): string
    {
        return $this->baseUrl . Str::start($url, '/');
    }

}
