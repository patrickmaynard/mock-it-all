---
layout: default
---

`mock-it-all` automatically creates PHPUnit test classes with mocked constructor dependencies — so you can get from a production class to a ready-to-use test setup in seconds.  

*Give it a class. Get a test.*

## The problem

*Testing shouldn't start with plumbing.*

Your class has a constructor with five dependencies.
Before you can test a single line of actual behavior, you have to create mocks for every one of them, wire everything up and instantiate the class under test.

You know the pattern. You’ve written it dozens of times.

`mock-it-all` takes care of it.

1. **Tell us what you want to test**  
   Provide the fully qualified class name of the class you want to test.
   ```php
   App\Service\OrderService
   ```
2. **Let mock-it-all do the tedious work**  
   The constructor is inspected and its dependencies are automatically turned into mocks.
3. **Get a ready-to-use test**  
   A test class is generated directly in your test directory — with the required mocks and the class under test already wired up.

**Less setup. More testing.**

## Features

**Automatic constructor mocking**  
Detect constructor dependencies and create the required mocks automatically.

**Ready-to-use test classes**  
Generate a test class in your test directory instead of starting from an empty file.

**CLI-first workflow**  
Works right where you already work: in your terminal and alongside your Composer project.

**Interactive assistant**  
Prefer a guided workflow? Let the interactive assistant walk you through creating your test.

**Built for PHP & PHPUnit**  
Designed around the tools and workflows PHP developers already use.
