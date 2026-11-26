<?php
declare(strict_types=1);

namespace Yireo\CleanModules\Test\Integration\Console\Command;

use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Console\CommandListInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use Magento\Framework\Phrase;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\CleanModules\Console\Command\CleanModules;

class CleanModulesTest extends AbstractCommandTestCase
{
    private string $configDirectory;

    /** @var Writer&MockObject */
    private Writer $writer;

    protected function setUp(): void
    {
        $this->configDirectory = sys_get_temp_dir() . '/yireo-clean-modules-' . uniqid();
        mkdir($this->configDirectory);
        file_put_contents($this->configDirectory . '/config.php', "<?php return ['modules' => []];\n");
        $this->writer = $this->createMock(Writer::class);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->configDirectory . '/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->configDirectory);
    }

    public function testCommandIsRegistered(): void
    {
        $commands = Bootstrap::getObjectManager()->get(CommandListInterface::class)->getCommands();
        $command = $commands['Yireo_CleanModules::cleanModules'] ?? null;

        $this->assertInstanceOf(CleanModules::class, $command);
        $this->assertSame('yireo:modules:clean', $command->getName());
    }

    public function testDoesNothingWithoutMissingModules(): void
    {
        $this->writer->expects($this->never())->method('saveConfig');

        $commandTester = $this->execute(['Magento_Store' => 1], ['--force' => true]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('No missing modules found', $commandTester->getDisplay());
        $this->assertSame([], $this->getBackupFiles());
    }

    public function testDryRunDoesNotWrite(): void
    {
        $this->writer->expects($this->never())->method('saveConfig');

        $commandTester = $this->execute(['Magento_Store' => 1, 'Yireo_Missing' => 1], ['--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Module does not exist: Yireo_Missing', $commandTester->getDisplay());
        $this->assertSame([], $this->getBackupFiles());
    }

    public function testNonInteractiveWithoutForceAborts(): void
    {
        $this->writer->expects($this->never())->method('saveConfig');

        $commandTester = $this->execute(['Yireo_Missing' => 1], [], ['interactive' => false]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Non-interactive mode requires --force', $commandTester->getDisplay());
    }

    public function testDeclinedConfirmationAborts(): void
    {
        $this->writer->expects($this->never())->method('saveConfig');

        $commandTester = $this->execute(['Yireo_Missing' => 1], [], ['interactive' => true], ['n']);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertSame([], $this->getBackupFiles());
    }

    public function testConfirmationDisablesOnlyMissingModules(): void
    {
        $this->expectSavedModules(['Magento_Store' => 1, 'Yireo_Missing' => 0, 'Yireo_Disabled' => 0]);

        $commandTester = $this->execute(
            ['Magento_Store' => 1, 'Yireo_Missing' => 1, 'Yireo_Disabled' => 0],
            [],
            ['interactive' => true],
            ['y']
        );

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Disabled module: Yireo_Missing', $commandTester->getDisplay());
        $this->assertCount(1, $this->getBackupFiles());
    }

    public function testForceDisablesMissingModulesAndCreatesBackup(): void
    {
        $this->expectSavedModules(['Magento_Store' => 1, 'Yireo_Missing' => 0]);

        $commandTester = $this->execute(['Magento_Store' => 1, 'Yireo_Missing' => 1], ['--force' => true]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $backupFiles = $this->getBackupFiles();
        $this->assertCount(1, $backupFiles);
        $this->assertFileEquals($this->configDirectory . '/config.php', $backupFiles[0]);
    }

    public function testRefusesToDisableCoreModuleWithoutForce(): void
    {
        $this->writer->expects($this->never())->method('saveConfig');

        $commandTester = $this->execute(['Magento_DoesNotExist' => 1], [], ['interactive' => true], ['y']);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Refusing to disable core modules', $commandTester->getDisplay());
    }

    public function testRefusesToDisableTooManyModulesWithoutForce(): void
    {
        $this->writer->expects($this->never())->method('saveConfig');

        $modules = [];
        for ($i = 0; $i <= CleanModules::MAX_MODULES_WITHOUT_FORCE; $i++) {
            $modules['Yireo_Missing' . $i] = 1;
        }

        $commandTester = $this->execute($modules, [], ['interactive' => true], ['y']);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Refusing to disable more than', $commandTester->getDisplay());
    }

    public function testRemoveDeletesMissingModules(): void
    {
        $this->expectSavedModules(['Magento_Store' => 1]);

        $commandTester = $this->execute(
            ['Magento_Store' => 1, 'Yireo_Missing' => 1, 'Yireo_OldMissing' => 0],
            ['--remove' => true, '--force' => true]
        );

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Removed module: Yireo_Missing', $commandTester->getDisplay());
        $this->assertStringContainsString('Removed module: Yireo_OldMissing', $commandTester->getDisplay());
        $this->assertCount(1, $this->getBackupFiles());
    }

    public function testRemoveKeepsExistingDisabledModules(): void
    {
        $this->expectSavedModules(['Magento_Store' => 0]);

        $commandTester = $this->execute(
            ['Magento_Store' => 0, 'Yireo_Missing' => 1],
            ['--remove' => true, '--force' => true]
        );

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function testRemoveDryRunDoesNotWrite(): void
    {
        $this->writer->expects($this->never())->method('saveConfig');

        $commandTester = $this->execute(['Yireo_OldMissing' => 0], ['--remove' => true, '--dry-run' => true]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertStringContainsString('Module does not exist: Yireo_OldMissing', $commandTester->getDisplay());
        $this->assertSame([], $this->getBackupFiles());
    }

    public function testRemoveRefusesCoreModuleWithoutForce(): void
    {
        $this->writer->expects($this->never())->method('saveConfig');

        $commandTester = $this->execute(
            ['Magento_DoesNotExist' => 0],
            ['--remove' => true],
            ['interactive' => true],
            ['y']
        );

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Refusing to remove core modules', $commandTester->getDisplay());
    }

    public function testFailsWithInvalidConfig(): void
    {
        $this->writer->expects($this->never())->method('saveConfig');

        $commandTester = $this->execute(false, ['--force' => true]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    public function testFailsWhenWriteFails(): void
    {
        $this->writer->method('saveConfig')->willThrowException(new FileSystemException(new Phrase('Not writable')));

        $commandTester = $this->execute(['Yireo_Missing' => 1], ['--force' => true]);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Not writable', $commandTester->getDisplay());
    }

    /**
     * @param array<string, int> $modules
     */
    private function expectSavedModules(array $modules): void
    {
        $this->writer->expects($this->once())
            ->method('saveConfig')
            ->with([ConfigFilePool::APP_CONFIG => ['modules' => $modules]], true);
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $options
     * @param string[] $inputs
     */
    private function execute(
        mixed $modules,
        array $input,
        array $options = ['interactive' => false],
        array $inputs = []
    ): CommandTester {
        $objectManager = Bootstrap::getObjectManager();
        $directoryList = new DirectoryList(
            $this->configDirectory,
            [DirectoryList::CONFIG => [DirectoryList::PATH => $this->configDirectory]]
        );

        $command = $objectManager->create(CleanModules::class, [
            'moduleConfig' => $this->createModuleConfig($modules),
            'writer' => $this->writer,
            'filesystem' => $objectManager->create(Filesystem::class, ['directoryList' => $directoryList]),
        ]);

        $commandTester = new CommandTester($command);
        $commandTester->setInputs($inputs);
        $commandTester->execute($input, $options + ['decorated' => false]);

        return $commandTester;
    }

    /**
     * @return string[]
     */
    private function getBackupFiles(): array
    {
        return glob($this->configDirectory . '/config.php.bak-*') ?: [];
    }
}
