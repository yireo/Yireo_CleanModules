<?php
declare(strict_types=1);

namespace Yireo\CleanModules\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use UnexpectedValueException;
use Yireo\CleanModules\Module\ModuleConfig;

class CheckModules extends Command
{
    public function __construct(
        private ModuleConfig $moduleConfig,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('yireo:modules:check');
        $this->setDescription('Check all modules');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $missingModules = $this->moduleConfig->getMissingModules($this->moduleConfig->getModules());
        } catch (UnexpectedValueException $exception) {
            $output->writeln('<error>' . OutputFormatter::escape($exception->getMessage()) . '</error>');
            return Command::FAILURE;
        }

        foreach ($missingModules as $moduleName) {
            $output->writeln('<error>Module does not exist: ' . OutputFormatter::escape($moduleName) . '</error>');
        }

        return empty($missingModules) ? Command::SUCCESS : Command::FAILURE;
    }
}
