<?php

namespace App\Command;

use App\Services\MHService;
use App\Services\OptimizerService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCharmsCommand extends Command
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
            ->setName('app:generate-charms')
            ->setDescription('Compute every possible charm with a given set of skills');
//            ->addArgument('strings', InputArgument::IS_ARRAY, 'An array of strings');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->optimizerService->getCharms();
        $output->write($result);

        return Command::SUCCESS;
    }
}