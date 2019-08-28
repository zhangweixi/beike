<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\AnalysisMatchData;

class Court extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'court:init {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $job = new AnalysisMatchData();
        $job->call_matlab_court_action($this->argument('id'));
    }
}
