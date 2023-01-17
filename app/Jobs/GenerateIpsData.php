<?php

namespace App\Jobs;

use App\Services\ConvertShipmentDataToIpsXml;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateIpsData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(protected $file)
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        resolve(ConvertShipmentDataToIpsXml::class)->handle($this->file);
    }
}
