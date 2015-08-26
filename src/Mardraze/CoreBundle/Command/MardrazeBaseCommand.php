<?php

namespace Mardraze\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MardrazeBaseCommand extends Command{


    /**
     * @var \Mardraze\CoreBundle\Service\Depedencies
     */
    protected $depedencies;

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    protected function configure() {
        $this->setName('mardraze:base');
    }
    public function run(InputInterface $input, OutputInterface $output)
    {
        $this->depedencies = $this->getApplication()->getKernel()->getContainer()->get('mardraze_core.depedencies');
        $this->depedencies->setupRouter();
        $this->input = $input;
        $this->output = $output;
        return parent::run($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}