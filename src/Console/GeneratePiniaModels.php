<?php

namespace Dev1437\PiniaModelGenerator\Console;

use Dev1437\PiniaModelGenerator\PiniaModelsBuilder;
use Illuminate\Console\Command;

class GeneratePiniaModels extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'piniamodels:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Pinia ORM models from your laravel models';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $pmb = new PiniaModelsBuilder();

        $pmb->buildModels();

        return Command::SUCCESS;
    }
}
