# Yireo_CleanModules

<!-- badges.specs.start -->
<div class="github-specs-badges">

![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-clean-modules)

</div>
<!-- badges.specs.end -->


> This Magento 2 module adds a CLI command `yireo:modules:clean` to remove non-existing modules from `app/etc/config.php`

### Installation
Install this package via composer:
```bash
composer require yireo/magento2-clean-modules
```

Next, enable this module:
```bash
bin/magento module:enable Yireo_CleanModules
```

### Usage
```bash
bin/magento yireo:modules:check            # Lists enabled modules that do not exist (exit code 1 if any)
bin/magento yireo:modules:clean --dry-run  # Shows which modules would be disabled
bin/magento yireo:modules:clean            # Asks for confirmation, backs up app/etc/config.php and disables missing modules
bin/magento yireo:modules:clean --force    # Skips the confirmation and the safety check on core modules or more than 10 modules
```

## Current status

<!-- badges.test.start -->
<div class="github-pipeline-badges">

![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CleanModules/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CleanModules/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CleanModules/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CleanModules/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CleanModules/compile.yml?label=compile)

</div>
<!-- badges.test.end -->
