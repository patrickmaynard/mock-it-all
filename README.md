# mock-it-all
Tool for creating mock dependency chains automatically, letting you focus on the actual tests

Todo items:

* x Move most functionality to a class called MockLogicCreator
* x Create a composer.json file for autoloading
* x Update the folder structure to match the autoload file (creating src and test dirs)
* Define a dependency on the Symfony console component. (More steps are below.)
* Allow the user to type a bin/console command, then get text output directly.
* Set up an official git tag and any boilerplate to make this a packagist package.
  (See your existing two packagist projects to get a template for how to do this.)
  Then put it up on Packagist.
* Set up a few basic PHPUnit tests for your MockLogicCreator class.
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
