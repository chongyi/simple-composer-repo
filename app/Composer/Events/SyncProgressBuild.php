<?php
/**
 * SyncProgressBuild.php
 *
 * Creator:         chongyi
 * Create Datetime: 2017/5/21 16:47
 */

namespace App\Composer\Events;


use Symfony\Component\Console\Output\OutputInterface;

class SyncProgressBuild
{
    /**
     * @var int
     */
    public $total;

    /**
     * @var OutputInterface
     */
    public $output;

    /**
     * SyncProgressBuild constructor.
     * @param int $total
     * @param OutputInterface $output
     */
    public function __construct($total, OutputInterface $output)
    {
        $this->total = $total;
        $this->output = $output;
    }


}