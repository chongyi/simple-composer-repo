<?php
/**
 * ConsoleOutputTrait.php
 *
 * Creator:         chongyi
 * Create Datetime: 2017/5/14 16:51
 */

namespace App\Composer;


use Symfony\Component\Console\Output\OutputInterface;

trait ConsoleOutputTrait
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @param     $message
     * @param int $options
     */
    protected function output($message, $options = 0)
    {
        if (PHP_SAPI != 'cli') {
            return;
        }

        if (!$this->output) {
            $this->output = $this->manager->getApplication()->make(OutputInterface::class);
        }

        $this->output->writeln($message, $options);
    }
}