<?php

namespace App\Services\ProcessRecord;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessRecordRefresher
{
    public function refreshAfterResponse(int|string $ppf): void
    {
        dispatch(function () use ($ppf) {
            try {
                $result = $this->refresh($ppf);
                Log::info('Process record refresh done', ['ppf' => $ppf, 'result' => $result]);
            } catch (Throwable $e) {
                Log::warning('Process record refresh failed', ['ppf' => $ppf, 'error' => $e->getMessage()]);
            }
        })->afterResponse();
    }

    public function refresh(int|string $ppf): array
    {
        $response = Http::withHeaders([
            'X-API-Key' => config('services.process_record_api.key'),
        ])
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(60)
            ->post(rtrim(config('services.process_record_api.base_url'), '/') . "/process-record/{$ppf}/refresh");

        if ($response->failed()) {
            Log::warning('Process record API error', [
                'ppf'    => $ppf,
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new \RuntimeException(
                "Failed to refresh process record for PPF {$ppf}: " . $response->json('message', $response->status())
            );
        }

        return $response->json() ?? [];
    }
}
