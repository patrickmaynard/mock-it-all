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
* x Create a --show-all-classes option that allows the user to simply see a list
  of all available classes.
* x Have the wizard disallow the ability to hit "enter" before seeing which
  class name is selected.
* x Fix the small bug in which typing "FryCook" causes a rendering bug in which
  the selection is shown as "FryCookOfCommerce"
* x Provide some form of scrollability for very long autocomplete lists -- as,
  for example, when allowing vendor classes and starting to type
  "SebastianBergman" ... this way the terminal session doesn't freak out.
* x Have the wizard look for a "tests" folder or a "test" folder, then allow the
  user to autocomplete which descendent directory should hold the new stub.
* x If no "tests" or "test" folder is found, have the wizard balk.
* x If the wizard is not active, require that a tests folder path be submitted,
  and that it exist.
* x Now that the wizard is the default behavior, remove some of the more ponderous
  behaviors associated with explicitly telling the command to use a wizard.
  They're now somewhat redundant.
* x Have the command ALWAYS write output to a new test class unless we explicitly
  tell it to just dump out code.
* x Update the readme documentation to reflect all of these new behaviors.
  Actually, the readme can give just the simplest example (which uses a wizard),
  then tell the user to use the --help flag to get information on how to use the
  command without a wizard.
* x Make the autocomplete UX work in a more user-friendly way for both
  autocompletes (FQCN autocompletion and test stub directory autocompletion).
* x Play around extensively in a fresh session, exploring all the options and
  nailing down any big bugs.
* Abstract most of the command logic into a new class, which should contain at
  least four separate functions to get some of this bloat tamed. This will be
  preparation for the process of creating a new version of the command that
  integrates more closely with any existing SF application, should the library
  be installed in an existing SF app.
* Test some more.
* Create the new Symfony-integrated version of the command and test it.
* Test some more.
* Set up some basic PHPUnit test logic for your non-Symfony console command.
* Get feedback from a couple friends.
* Allow output of a test stub to a file in the project root instead of standard out
* Provide a default test class name that can be modified
* Provide a default test class namespace that can be modified
* Allow optional writing of a file within the actual test folder structure.
  This option can maybe allow creation of folders as well while we're at it.
* Set up git hooks so that a cs fixer, phpstan and all tests run at appropriate
  times.
*
