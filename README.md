# mock-it-all
Tool for creating mock dependency chains automatically, letting you focus on the actual tests

## How to use

In a pre-existing PHP project do the following to install the tool:

```
composer require patrick-maynard/mock-it-all
```

... then run the `create-test-stub-with-mocks` command as shown below.

(This example creates stub test logic for the included President demo class. It
does so only if a folder called tests/Unit already exists.)

```
php ./vendor/bin/mock-it-all create-test-stub-with-mocks --fqcn="PatrickMaynard\MockItAll\Stubs\President" --test-folder="tests/Unit"
```
... or if you want to use an interactive wizard to choose a class and path:

```
php ./vendor/bin/mock-it-all create-test-stub-with-mocks
```

To get some other options for the CLI command:

```
php ./vendor/bin/mock-it-all create-test-stub-with-mocks --help
```

It is recommended that developers install this in development environments,
not in test, stage or production environments.
