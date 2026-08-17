# Developer notes (public-facing)

Note to self: If testing outside of an app context, it might be necessary to
first symlink the mock-it-all directory like so:

```
ln -s `pwd`/bin/mock-it-all ./vendor/bin/mock-it-all
chmod +x ./bin/mock-it-all
```

## Todo items:

* x Move most functionality to a class called MockLogicCreator
* x Create a composer.json file for autoloading
* x Update the folder structure to match the autoload file (creating src and test dirs)
* x Define a dependency on the Symfony console component. (More steps are below.)
* x Allow the user to type a bin/console command, then get text output directly.
* x Set up an official git tag and any boilerplate to make this a packagist package.
  (See your existing two packagist projects to get a template for how to do this.)
  Then put it up on Packagist.
* x Test your new workflow on a local, dead-end copy of the Tag Monk
  application to see whether all the documentation is accurate.
* x If the documentation *is* accurate, move the note to self to a new file.
  Something like "development-team-notes-public.md"
* x Set up more few basic PHPUnit test logic for your MockLogicCreator class.
* x Do another local installation in a copy of the Tag Monk project, making
  sure that the console command has access to a class defition in the
  *wider Tag Monk application* -- you have not done this yet!
* x Add a --no-wizard flag to your console command.
* x Add a --with-wizard flag to your console command. For now, this should merely
  state that it will be the future default behavior, but doesn't exist yet.
* x Make the command fail loudly if no mode flag is provided.
* x Allow autocompletion of class names for the classes being tested.
  (This can be done with a --wizard flag, allowing a multi-step process.)
  (See how the make:entity command does it.)
  (Actually, the --wizard behavior should be the default behavior.)
  (A --no-wizard option can be added for those who don't want it.)
  (This is a breaking change, so a new major version should go along with it.)
* Have the wizard balk if the user just hits enter immediately, without entering
  part of a class name first.
* Have the wizard look for a "tests" folder or a "test" folder, then allow the
  user to autocomplete which descendent directory should hold the new stub.
* If no "tests" or "test" folder is found, have the wizard balk.
* Make the autocomplete UX work in a more user-friendly way for both
  autocompletes (FQCN autocompletion and test stub directory autocompletion).
* Play around extensively in a fresh session, exploring all the options and
  nailing down any big bugs.
* Set up some basic PHPUnit test logic for your console command.
* Get feedback from a couple friends.
* Allow output of a test stub to a file in the project root instead of standard out
* Provide a default test class name that can be modified
* Provide a default test class namespace that can be modified
* Allow optional writing of a file within the actual test folder structure.
  This option can maybe allow creation of folders as well while we're at it.
* Set up git hooks so that a cs fixer, phpstan and all tests run at appropriate
  times.
*
