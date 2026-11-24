<?php
declare(strict_types=1);

namespace Yireo\CleanModules\Test\Integration\Console\Command;

use Magento\Framework\Console\CommandListInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\CleanModules\Console\Command\CheckModules;

class CheckModulesTest extends AbstractCommandTestCase
{
    public function testCommandIsRegistered(): void
    {
        $commands = Bootstrap::getObjectManager()->get(CommandListInterface::class)->getCommands();
        $command = $commands['Yireo_CleanModules::checkModules'] ?? null;

        $this->assertInstanceOf(CheckModules::class, $command);
        $this->assertSame('yireo:modules:check', $command->getName());
    }

    public function testSucceedsWithoutMissingModules(): void
    {
        $commandTester = $this->execute(['Magento_Store' => 1, 'Yireo_CleanModules' => 1]);

        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
        $this->assertSame('', $commandTester->getDisplay());
    }

    public function testFailsWithMissingModuleAndEscapesOutput(): void
    {
        $commandTester = $this->execute([
            'Magento_Store' => 1,
            'Yireo_Missing<info>Foo</info>' => 1,
            'Yireo_DisabledMissing' => 0,
        ]);

        $display = $commandTester->getDisplay();
        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('Module does not exist: Yireo_Missing<info>Foo</info>', $display);
        $this->assertStringNotContainsString('Yireo_DisabledMissing', $display);
    }

    public function testFailsWithInvalidConfig(): void
    {
        $commandTester = $this->execute(null);

        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
        $this->assertStringContainsString('No valid "modules" section', $commandTester->getDisplay());
    }

    private function execute(mixed $modules): CommandTester
    {
        $command = Bootstrap::getObjectManager()->create(
            CheckModules::class,
            ['moduleConfig' => $this->createModuleConfig($modules)]
        );

        $commandTester = new CommandTester($command);
        $commandTester->execute([], ['decorated' => false]);

        return $commandTester;
    }
}
