<?php
declare(strict_types=1);

namespace App\Command;

use App\Service\FoodServiceInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:food:seed-json',
    description: 'Load request.json and save initial Fruits/Vegetables to Redis via FoodService'
)]
class SeedFoodCommand extends Command
{
    /**
     * @param FoodServiceInterface $foodService
     */
    public function __construct(private readonly FoodServiceInterface $foodService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'file',
            InputArgument::REQUIRED,
            'Path to request.json (e.g., request.json)'
        );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = (string)$input->getArgument('file');
        if (!is_file($path)) {
            $output->writeln("<error>File not found: {$path}</error>");
            return Command::FAILURE;
        }

        try {
            $data = json_decode((string)file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $output->writeln("<error>Invalid JSON: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }

        foreach ($data as $food) {
            $this->foodService->add($food['name'], $food['type'], $food['quantity'], $food['unit']);
        }

        $output->writeln('<info>Seed complete ✅</info>');
        return Command::SUCCESS;
    }
}
