<?php

namespace App\Console\Commands;

use App\Composer\PackageManager;
use App\Composer\ProxyPackage;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class RepoProxyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repo:proxy {--async}';

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
        if (!$this->laravel->bound(OutputInterface::class)) {
            $this->laravel->instance(OutputInterface::class, $this->getOutput());
        }

        $this->getLaravel()->make(PackageManager::class)->sync($this->option('async'));

        return 0;
    }
}
