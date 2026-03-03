# mock-it-all
Tool for creating mock dependency chains automatically, letting you focus on the actual tests

Todo items: 

* Move most functionality to a class called MockLogicCreator 
* Create a package for this by defining a dependency on the Symfony console component. 
* Allow the user to type a bin/console command, then text output directly. 
* Set up an official git tag and any boilerplate to make this a packagist package.
  Then put it up on Packagist.
* Set up a few basic PHPUnit tests for your MockLogicCreator class.
* Set up another couple basic PHPUnit tests for your console command. 
* Get feedback from a couple friends.
* Allow autocompletion of class names. (See how the make:entity command does it.)
* Allow output of a test stub to a file in the project root instead of standard out
* Provide a default test class name that can be modified
* Provide a default test class namespace that can be modified
* Allow optional writing of a file within the actual test folder structure.
  This option can allow creation of folders as well while we're at it.
* 
