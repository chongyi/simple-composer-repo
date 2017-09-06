<?php

namespace App\Console\Commands;

use App\Composer\LocalPackages;
use Illuminate\Console\Command;

class RepoLocalCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repo:local {operate} {name?} {--P|provider-include=null}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Local repo manage.';

    /**
     * @var LocalPackages
     */
    protected $localPackages;

    protected $supportOperateList = [
        'list',
        'add',
    ];

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
        if (!in_array($operate = $this->argument('operate'), $this->supportOperateList)) {
            throw new \InvalidArgumentException();
        }

        if (!method_exists($this, $method = $operate . 'Operate')) {
            throw new \InvalidArgumentException();
        }

        call_user_func([$this, $method]);

        return 0;
    }

    /**
     * @return LocalPackages
     */
    private function getLocalPackages()
    {
        if (is_null($this->localPackages)) {
            return $this->localPackages = $this->laravel->make(LocalPackages::class);
        }

        return $this->localPackages;
    }

    protected function listOperate()
    {
        dd($this->getLocalPackages()->getPackageRegisters());
    }

    protected function addOperate()
    {
        $packageName = $this->argument('name');
        $providerInclude = $this->option('provider-include');

        if ($providerInclude === 'null') {
            $providerInclude = null;
        }

        $this->getLocalPackages()->register($packageName, $providerInclude);
    }
}
