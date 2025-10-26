# CONTRIBUTING

Make sure you read our [contributing guide][contributing guide on the website].

[contributing guide on the website]:https://www.doctrine-project.org/contribute

## Installing dependencies

```shell
composer install
```

## Running checks locally

Here is a script to run all checks, you can use it as a git hook:

```shell
#!/bin/bash -eu
vendor/bin/phpunit --testdox
echo '' | vendor/bin/phpcs
vendor/bin/phpstan analyze
```

## Regenerating expected output

To regenerate expected tests output, run `bin/regenerate-expected-output`.
