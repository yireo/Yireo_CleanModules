<?php
declare(strict_types=1);

namespace Yireo\CleanModules\Module;

use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use UnexpectedValueException;

class ModuleConfig
{
    public function __construct(
        private DeploymentConfig $deploymentConfig,
        private ComponentRegistrarInterface $componentRegistrar
    ) {
    }

    /**
     * @return array<string, int>
     * @throws UnexpectedValueException
     */
    public function getModules(): array
    {
        $modules = $this->deploymentConfig->get('modules');
        if (!is_array($modules) || empty($modules)) {
            throw new UnexpectedValueException('No valid "modules" section found in app/etc/config.php');
        }

        $result = [];
        foreach ($modules as $moduleName => $moduleStatus) {
            if (!is_string($moduleName) || !is_scalar($moduleStatus)) {
                throw new UnexpectedValueException('Invalid entry in "modules" section of app/etc/config.php');
            }

            $result[$moduleName] = (int)$moduleStatus;
        }

        return $result;
    }

    /**
     * @param array<string, int> $modules
     * @return string[]
     */
    public function getMissingModules(array $modules): array
    {
        $registeredModules = $this->componentRegistrar->getPaths(ComponentRegistrar::MODULE);
        $missingModules = [];
        foreach ($modules as $moduleName => $moduleStatus) {
            if ($moduleStatus === 1 && !isset($registeredModules[$moduleName])) {
                $missingModules[] = $moduleName;
            }
        }

        return $missingModules;
    }
}
