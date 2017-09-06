<?php
/**
 * SyncProgressSubscriber.php
 *
 * Creator:         chongyi
 * Create Datetime: 2017/5/21 22:22
 */

namespace App\Subscribers;


use App\Composer\Events\SyncOnePackagesItem;
use App\Composer\Events\SyncProgressBuild;
use App\Composer\Events\SyncProgressFinish;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Helper\ProgressBar;

class SyncProgressSubscriber
{
    /**
     * @var ProgressBar
     */
    protected $progress;

    protected $windowsProgress;

    public function onBuild(SyncProgressBuild $event)
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            $numberStringLength = strlen((string)$event->total);
            $this->windowsProgress = function () use ($numberStringLength, $event) {
                static $count = 0;
                $count++;
                printf("\r    %{$numberStringLength}s/%s %3s%%", $count, $event->total,
                    floor($count / $event->total * 100));
            };
        } else {
            $this->progress = new ProgressBar($event->output, $event->total);
            $this->progress->start();
        }

    }

    public function onPackageAdded()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            call_user_func($this->windowsProgress);
        } else {
            $this->progress->advance();
        }

    }

    public function onFinish()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            print "\n";
        } else {
            $this->progress->finish();
            print "\n";
        }
    }

    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            SyncProgressBuild::class,
            static::class . '@onBuild'
        );

        $events->listen(
            SyncOnePackagesItem::class,
            static::class . '@onPackageAdded'
        );

        $events->listen(
            SyncProgressFinish::class,
            static::class . '@onFinish'
        );
    }
}