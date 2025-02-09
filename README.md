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

Or you can install it globally

```shell
composer global require xandrw/architecture-enforcer
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
        // not a PHP Core member will result in an error
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
  'App\Domain': [ ]
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

### Additional Information

- **Tests:** for more details, refer to the `tests` included with the project.

---

### Roadmap

- [x] `validate` command that validates your application files against the config
- [x] Execution time and memory used
- [x] Refactor processes to OOP
- [ ] `--errors/-e` display only the errors, not all the scanned files
- [ ] `--only/-o` optional parameter for `validate` which would do the opposite of `--ignore/-i`
- [ ] `--no-circular` optional parameter for `validate` that restricts circular dependencies between layers
- [ ] `--pure` Pure mode, where the defined architecture layers must exist, meaning the directory structure should be
  there
- [ ] `debug` command that shows all files, their namespace, the layer they belong to and the used layers/namespaces