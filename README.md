# PHP Architecture Enforcer
A simple command-line tool that recursively scans a directory, analyzes your PHP files
for `namespace` and `use` statements, and checks them against a defined `architecture` config.
If a file uses a `dependency` that is outside the allowed `scope` for its `layer`, the command will continue scanning,
collect any errors along with their file names and offending line numbers, then display all the errors.

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
After you've configured your architecture, let's say your project `root` is at
`/Users/your-user/project-root` and has a `src` directory where your application files are located.
```shell
./vendor/bin/enforcer validate /Users/your-user/project-root/src /Users/your-user/project-root/config/architecture.php/yml/yaml
```

**With ignored paths**
```shell
./vendor/bin/enforcer validate -i Domain/Interfaces,Infrastructure /Users/your-user/project-root/src /Users/your-user/project-root/config/architecture.php/yml/yaml
```

#### Command Signature
```
./vendor/bin/enforcer validate [--ignore/-i] [--] <path-to-source> <path-to-config>
```

**Or you can run:**
```shell
./vendor/bin/enforcer validate -h
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
No architecture issues found
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
App\Domain\Entities\SomeDomainEntity:6 cannot use App\Infrastructure\SomeInfrastructureClass
```

---

### Additional Information
- **Tests:** for more details, refer to the `tests` included with the project.
- **Work in Progress:** This tool is a work in progress (`WIP`), but can be used as is.