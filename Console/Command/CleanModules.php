<?php
declare(strict_types=1);

namespace Yireo\CleanModules\Console\Command;

use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use UnexpectedValueException;
use Yireo\CleanModules\Module\ModuleConfig;

class CleanModules extends Command
{
    public const MAX_MODULES_WITHOUT_FORCE = 10;

    public function __construct(
        private ModuleConfig $moduleConfig,
        private Writer $writer,
        private Filesystem $filesystem,
        private ConfigFilePool $configFilePool,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName('yireo:modules:clean');
        $this->setDescription('Disable all modules in app/etc/config.php that do not exist');
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Only show which modules would be disabled');
        $this->addOption(
            'force',
            null,
            InputOption::VALUE_NONE,
            'Skip the confirmation and the safety check on core modules or many modules'
        );
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $modules = $this->moduleConfig->getModules();
        } catch (UnexpectedValueException $exception) {
            $output->writeln('<error>' . OutputFormatter::escape($exception->getMessage()) . '</error>');
            return Command::FAILURE;
        }

        $missingModules = $this->moduleConfig->getMissingModules($modules);
        if (empty($missingModules)) {
            $output->writeln('<info>No missing modules found</info>');
            return Command::SUCCESS;
        }

        foreach ($missingModules as $moduleName) {
            $output->writeln('Module does not exist: ' . OutputFormatter::escape($moduleName));
        }

        if ($input->getOption('dry-run')) {
            $output->writeln('<info>Dry run: app/etc/config.php was not modified</info>');
            return Command::SUCCESS;
        }

        $force = (bool)$input->getOption('force');
        if (!$force && !$this->isSafeToDisable($missingModules, $output)) {
            return Command::FAILURE;
        }

        if (!$force && !$this->confirm($input, $output, count($missingModules))) {
            $output->writeln('<error>Aborted: app/etc/config.php was not modified</error>');
            return Command::FAILURE;
        }

        foreach ($missingModules as $moduleName) {
            $modules[$moduleName] = 0;
        }

        try {
            $backupFile = $this->backupConfigFile();
            $output->writeln('Backup written to: ' . OutputFormatter::escape($backupFile));
            $this->writer->saveConfig([ConfigFilePool::APP_CONFIG => ['modules' => $modules]], true);
        } catch (FileSystemException $exception) {
            $output->writeln('<error>' . OutputFormatter::escape($exception->getMessage()) . '</error>');
            return Command::FAILURE;
        }

        foreach ($missingModules as $moduleName) {
            $output->writeln('<info>Disabled module: ' . OutputFormatter::escape($moduleName) . '</info>');
        }

        return Command::SUCCESS;
    }

    /**
     * @param string[] $missingModules
     */
    private function isSafeToDisable(array $missingModules, OutputInterface $output): bool
    {
        $coreModules = array_filter($missingModules, fn (string $moduleName) => str_starts_with($moduleName, 'Magento_'));
        if (!empty($coreModules)) {
            $output->writeln('<error>Refusing to disable core modules, use --force to override: '
                . OutputFormatter::escape(implode(', ', $coreModules)) . '</error>');
            return false;
        }

        if (count($missingModules) > self::MAX_MODULES_WITHOUT_FORCE) {
            $output->writeln('<error>Refusing to disable more than ' . self::MAX_MODULES_WITHOUT_FORCE
                . ' modules, use --force to override</error>');
            return false;
        }

        return true;
    }

    private function confirm(InputInterface $input, OutputInterface $output, int $count): bool
    {
        if (!$input->isInteractive()) {
            $output->writeln('<error>Non-interactive mode requires --force</error>');
            return false;
        }

        $question = new ConfirmationQuestion('Disable ' . $count . ' module(s) in app/etc/config.php? [y/N] ', false);

        return (bool)(new QuestionHelper())->ask($input, $output, $question);
    }

    /**
     * @throws FileSystemException
     */
    private function backupConfigFile(): string
    {
        $configDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::CONFIG);
        $configFile = $this->configFilePool->getPath(ConfigFilePool::APP_CONFIG);
        $backupFile = $configFile . '.bak-' . date('YmdHis');
        $configDirectory->copyFile($configFile, $backupFile);

        return $configDirectory->getAbsolutePath($backupFile);
    }
}
