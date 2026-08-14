# mock-it-all
Tool for creating mock dependency chains automatically, letting you focus on the actual tests

## How to use

In a pre-existing PHP project do the following to install the tool:

```
composer require patrick-maynard/mock-it-all
```

... then run the `create-test-stub-with-mocks` command as shown below.

(This example creates stub test logic for the included President demo class.)

```
php ./vendor/bin/mock-it-all create-test-stub-with-mocks "PatrickMaynard\MockItAll\Stubs\President"
```

It is recommended that developers install this in development environments,
not in test, stage or production environments.
