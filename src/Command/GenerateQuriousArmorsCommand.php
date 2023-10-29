<?php

namespace App\Command;

use App\Services\OptimizerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateQuriousArmorsCommand extends Command
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
            ->setName('app:generate-qurious-armors')
            ->setDescription('Generate all curious armor combinations with a given list of skills');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->optimizerService->getQuriousArmors();
        $output->write($result);

        return Command::SUCCESS;
    }
}
