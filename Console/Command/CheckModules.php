<?php
declare(strict_types=1);

namespace Yireo\CleanModules\Console\Command;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Module\ModuleList;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckModules extends Command
{
    public function __construct(
        private DirectoryList $directoryList,
        private ModuleList $moduleList,
        ?string $name = null) {
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
        $configFile = $this->directoryList->getRoot().'/app/etc/config.php';
        $config = include $configFile; // phpcs:ignore
        $allModules = $this->moduleList->getAll();

        foreach ($config['modules'] as $moduleName => $moduleStatus) {
            $moduleStatus = (int)$moduleStatus;
            if ($moduleStatus === 0) {
                continue;
            }

            $module = $this->moduleList->getOne($moduleName);
            if (empty($module)) {
                $output->writeln('<error>Module does not exist: '.$moduleName.'</error>');
            }
        }

        return Command::SUCCESS;
    }
}
