# mock-it-all
Tool for creating mock dependency chains automatically, letting you focus on the actual tests

## How to use

In a pre-existing PHP project do the following to install the tool:

```
composer require patrickmaynard/mock-it-all
```

... then run the `create-stub-with-mocks` command as shown below.

(This example generate stub test logic for the included President demo class.)

```
php ./vendor/bin/mock-it-all/execute create-test-stub-with-mocks "PatrickMaynard\MockItAll\Stubs\President"
```

Note to self: If testing outside of an app context, it might be necessary to
first symlink the mock-it-all directory like so:

```
ln -s `pwd`/bin/ ./vendor/bin/mock-it-all
chmod +x ./bin/execute
```


Todo items:

* x Move most functionality to a class called MockLogicCreator
* x Create a composer.json file for autoloading
* x Update the folder structure to match the autoload file (creating src and test dirs)
* x Define a dependency on the Symfony console component. (More steps are below.)
* x Allow the user to type a bin/console command, then get text output directly.
* Set up an official git tag and any boilerplate to make this a packagist package.
  (See your existing two packagist projects to get a template for how to do this.)
  Then put it up on Packagist.
* Test your new workflow on a local, dead-end copy of the Tag Monk application
  to see whether all the documentation is accurate.
* If the documentation *is* accurate, move the note to self to a new file.
  Something like "development-team-notes-public.md"
* Set up a more few basic PHPUnit tests for your MockLogicCreator class.
* Set up another couple basic PHPUnit tests for your console command.
* Get feedback from a couple friends.
* Allow autocompletion of class names for the classes being tested.
  (See how the make:entity command does it.)
* Allow output of a test stub to a file in the project root instead of standard out
* Provide a default test class name that can be modified
* Provide a default test class namespace that can be modified
* Allow optional writing of a file within the actual test folder structure.
  This option can maybe allow creation of folders as well while we're at it.
*
