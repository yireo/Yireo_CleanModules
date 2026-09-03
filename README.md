# Yireo_CleanModules

<!-- badges.specs.start -->
![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-clean-modules)
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

## Current status

<!-- badges.test.start -->
![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CleanModules/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CleanModules/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CleanModules/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CleanModules/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_CleanModules/compile.yml?label=compile)
<!-- badges.test.end -->

