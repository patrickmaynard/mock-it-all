# Developer notes (public-facing)

Note to self: If testing outside of an app context, it might be necessary to
first symlink the mock-it-all directory like so:

```
ln -s `pwd`/bin/mock-it-all ./vendor/bin/mock-it-all
chmod +x ./bin/mock-it-all
```

## Todo items:

* x Move old finished todo items into a document called finished-todos-public.md
* x Test some more.
* x Set up some basic PHPUnit test logic for your non-Symfony console command.
* Get feedback from a couple friends.
* Allow output of a test stub to a file in the project root instead of standard out
* Provide a default test class name that can be modified
* Provide a default test class namespace that can be modified
* Allow optional writing of a file within the actual test folder structure.
  This option can maybe allow creation of folders as well while we're at it.
* Set up git hooks so that a cs fixer, phpstan and all tests run at appropriate
  times.
* Move old finished todo items onto the end of finished-todos-public.md
* Create a new packagist package that encapsulates a Symfony bundle, which
  creates a main-application command that calls out to this library's logic,
  much like the existing bundles for doctrine and twig add those libraries'
  functionality to SF apps.
