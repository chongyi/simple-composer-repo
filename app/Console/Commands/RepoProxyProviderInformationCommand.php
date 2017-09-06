<?php

namespace App\Console\Commands;

use GuzzleHttp\Client;
use Illuminate\Console\Command;

class RepoProxyProviderInformationCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'repo:proxy-provider-info';

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
        $this->output->writeln(sprintf('<fg=blue;options=bold>Proxy providers from %s</>', config('repo.proxy.url')));

        $client = new Client(['base_uri' => config('repo.proxy.url'), 'verify' => false]);
        $response = $client->get('packages.json');

        $packagesJson = json_decode($response->getBody()->getContents(), true);

        if (isset($packagesJson['provider-includes'])) {
            foreach ($packagesJson['provider-includes'] as $include => $hashData) {
                $hash = current($hashData);

                $response = $client->get(str_replace('%hash%', $hash, $include));
                $providers = json_decode($response->getBody()->getContents(), true);

                $this->output->writeln(sprintf('==> <comment>%s</comment>: packages count: %s', $include,
                    count($providers['providers'])));
                $this->output->writeln('    HASH:sha256:' . $hash);
            }
        }

        return 0;
    }
}
