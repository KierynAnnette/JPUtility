<?php

namespace App\Console\Commands;

use App\Jobs\GenerateIpsData;
use Illuminate\Console\Command;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Support\Facades\Storage;

class ConvertIpsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:name';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convery csv Shipment data to xml for IPS';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $files = array_filter(
                Storage::disk('public')->allFiles('/IPS'),
                function ($item) {
                    return strpos($item, '.csv');
                });

        // Process files.
        foreach($files as $file) {
            $job = new GenerateIpsData($file);
            app(Dispatcher::class)->dispatch($job->onQueue('high'));
        }

        return Command::SUCCESS;
    }
}
