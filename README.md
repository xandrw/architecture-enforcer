# PHP Architecture Enforcer

The PHP Architecture Enforcer is a simple command-line tool that recursively scans a directory,
analyzes your PHP files for `namespace` and `use` statements, and checks them against a defined architecture configuration.
If a file uses a `dependency` that is outside the allowed scope for its layer, the command will stop execution and display
an error, including the file `name` and the offending `line` number.

---

### Installation
Install the tool as a development dependency using Composer:
```shell
composer require --dev xandrw/architecture-enforcer
```

---

### Configuration

Create an architecture configuration file (e.g., architecture.php) in your project.
This file defines your application’s layers and allowed dependencies.

#### Example: Clean Architecture
```php
// project-root/config/architecture.php
<?php

return [
    // 'architecture' is required, it contains the layers in your application
    'architecture' => [
        'App\\Presentation' => [
            'App\\Application',
            'App\\Domain',
        ],
        'App\\Infrastructure' => [
            'App\\Application',
            'App\\Domain',
        ],
        'App\\Application' => ['App\\Domain'],
        // A layer key that has itself contained in the child array
        // will be marked as strict. Any dependency not referenced
        // in the children array, not part of the current layer or
        // not a PHP Core member will error 
        // 'App\\Domain' => ['App\\Domain'],
        'App\\Domain' => [],
    ],
    // 'ignore' is not required, as these paths can be passed with the ignore parameter
    'ignore' => ['bin', 'config', 'public', 'tests', 'var', 'vendor'],
];
```

**Or if you prefer yaml:**
```yaml
# project-root/config/architecture.yml/yaml
architecture:
    'App\Presentation':
        - 'App\Infrastructure'
        - 'App\Application'
        - 'App\Domain'
    'App\Infrastructure':
        - 'App\Application'
        - 'App\Domain'
    'App\Application':
        - 'App\Domain'
    'App\Domain': []
ignore:
    - bin
    - config
    - public
    - tests
    - var
    - vendor
```

---

### Usage
After you've configured your architecture, you'll need to `cd` to your project's `root` directory.
Let's say your project `root` is at `/Users/your-user/project-root` and has a `src` directory where your application files are located.
```shell
./vendor/bin/enforcer validate:architecture src config/architecture.php
```

#### Command Signature

```
./vendor/bin/enforcer validate:architecture [options] [--] <path-to-source> <path-to-config>
```

**Or you can run:**
```shell
./vendor/bin/enforcer validate:architecture -h
```

---

### Output Examples

#### Successful Scan
```
Scanning directory: /Users/your-user/project-root/src
Scanned: /Users/your-user/project-root/src/Application/SomeApplicationClass.php
Scanned: /Users/your-user/project-root/src/Infrastructure/SomeInfrastructureClass.php
Scanned: /Users/your-user/project-root/src/Domain/Entities/SomeDomainEntity.php
Scanned: /Users/your-user/project-root/src/Presentation/Endpoints/SomePresentationEndpoint.php
```

#### Error Scan
If a file violates the architectural rules, the output will indicate the failure and provide details.
For example, consider this file:
```php
// /Users/your-user/project-root/src/Domain/Entities/SomeDomainEntity.php
<?php

namespace App\Domain\Entities;

use App\Infrastructure\SomeInfrastructureClass;

class SomeDomainEntity
{
    public function __construct() {
        $class = SomeInfrastructureClass::class;
    }
}
```

```
Scanning directory: /Users/your-user/project-root/src
Failed: /Users/your-user/project-root/src/Domain/Entities/SomeDomainEntity.php
App\Domain\Entities\SomeDomainEntity:44 cannot use App\Infrastructure\SomeInfrastructureClass
```

---

### Additional Information

- **Tests:** for more examples and detailed usage, refer to the tests included with the project.
- **Work in Progress:** This tool is a work in progress (WIP), and further improvements or features may be added over time.