<?php

namespace App\Console\Commands;

use App\Composer\PackageManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\OutputInterface;

class RepoProxyAsyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repo:proxy-async {--include-name=} {--proxy-include-hash=} {--provider-url=} {--proxy-old-include-hash=}';

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

        $proxyOldIncludeHash = $this->option('proxy-old-include-hash');
        $proxyOldIncludeHash = $proxyOldIncludeHash === 'null' ? null : $proxyOldIncludeHash;

        $this->getLaravel()->make(PackageManager::class)->syncProviderInclude(
            $this->option('include-name'),
            $this->option('proxy-include-hash'),
            $this->option('provider-url'),
            $proxyOldIncludeHash,
            PackageManager::SYNC_OPTION_ASYNC
        );

        return 0;
    }
}
