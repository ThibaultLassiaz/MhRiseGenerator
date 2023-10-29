<?php

namespace App\Command;

use App\Services\OptimizerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateArmorsCommand extends Command
{
    private OptimizerService $optimizerService;

    public function __construct(OptimizerService $optimizerService)
    {
        parent::__construct();

        $this->optimizerService = $optimizerService;
    }

    protected function configure()
    {
        $this
            ->setName('app:generate-armors')
            ->setDescription('Get all interesting armors as a json with all their affixes and slots');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->optimizerService->getArmors();
        $output->write($result);

        return Command::SUCCESS;
    }
}
