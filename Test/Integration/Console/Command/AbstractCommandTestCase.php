<?php
declare(strict_types=1);

namespace Yireo\CleanModules\Test\Integration\Console\Command;

use Magento\Framework\App\DeploymentConfig;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Yireo\CleanModules\Module\ModuleConfig;

abstract class AbstractCommandTestCase extends TestCase
{
    /**
     * @param mixed $modules
     */
    protected function createModuleConfig(mixed $modules): ModuleConfig
    {
        /** @var DeploymentConfig&MockObject $deploymentConfig */
        $deploymentConfig = $this->createMock(DeploymentConfig::class);
        $deploymentConfig->method('get')->with('modules')->willReturn($modules);

        return Bootstrap::getObjectManager()->create(ModuleConfig::class, ['deploymentConfig' => $deploymentConfig]);
    }
}
