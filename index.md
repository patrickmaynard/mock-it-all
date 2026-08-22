---
layout: default
---

`mock-it-all` automatically creates PHPUnit test classes with mocked constructor dependencies — so you can go from a production class to a ready-to-use test in seconds.

**Give it a class. Get a test.**

[Get Started](#get-started) · [See how it works](#how-it-works)

---

## The problem

**Testing shouldn't start with plumbing.**

Your class has a constructor with several dependencies.

Before you can test a single line of actual behavior, you have to create mocks, wire everything up and instantiate the class under test.

You know the pattern. You’ve written it dozens of times.

And while none of it is particularly challenging, it is repetitive, mechanical work that has very little to do with what you actually want to test.

`mock-it-all` takes care of it.

### From this

```text
Create mock
Create mock
Create mock
Create mock
Wire dependencies
Instantiate subject
Finally write the test
```

### To this

```text
Give it a class
     ↓
Get a test
     ↓
Write the behavior you actually want to test
```

**Less setup. More testing.**

----

## How it works

### 1. Pick a class

Provide the fully qualified class name of the class you want to test.

```php
App\Service\OrderService
```

That's all you need to start.

### 2. Skip the boilerplate

`mock-it-all` inspects the constructor and automatically creates mocks for its dependencies.

No manually creating every mock.
No manually wiring every dependency.
No starting from an empty test file.

### 3. Start testing

A ready-to-use PHPUnit test class is generated directly in your test directory — with the required mocks and the class under test already wired up.

The boring part is done.

Now you can focus on the reason the test exists in the first place: **testing behavior.**

----

## Built to remove repetitive work

### ⚡ Skip mock boilerplate

Constructor dependencies are detected and mocked automatically, so you don't have to set them up by hand.

### 🧪 Start with a real test

Instead of an empty test class, start with the structure you actually need — mocks, dependencies and the class under test already in place.

### 🔄 Keep up with changing constructors

When a class gains or loses constructor dependencies, regenerating its test setup means you don't have to manually recreate the plumbing.

### ⌨️ Stay in your workflow

Generate tests directly from your terminal, right alongside your Composer project.

### 🤝 Use it your way

Prefer a quick command? Use the CLI.

Prefer a guided workflow? Use the interactive assistant.

### 🐘 Made for PHP & PHPUnit

`mock-it-all` is designed around the tools and testing workflow PHP developers already use.

---

## A test should start here

Imagine you're adding a new service:

```php
final class OrderService
{
    public function __construct(
        OrderRepository $orders,
        PaymentGateway $payments,
        EventDispatcher $events,
    ) {
        // ...
    }
}
```

Normally, your first step in writing the test is to reproduce that constructor setup:

```php
$orders = $this->createMock(OrderRepository::class);
$payments = $this->createMock(PaymentGateway::class);
$events = $this->createMock(EventDispatcher::class);

$service = new OrderService(
    $orders,
    $payments,
    $events,
);
```

None of this is the behavior you're trying to test.

It's just preparation.

With `mock-it-all`, that preparation can be generated for you.

You can start with the test itself:

```php
public function test_it_processes_an_order(): void
{
    // Arrange
    
    // Act
    
    // Assert
}
```
**Your test should spend its time describing behavior — not reconstructing the dependency graph.**

---

## Designed for the everyday test

`mock-it-all` is especially useful when you're working with classes that use constructor dependency injection and have several collaborators.

Instead of repeatedly writing the same setup, you get a consistent starting point for every test.

That means:

* less repetitive typing
* less test setup to maintain
* fewer opportunities to forget a dependency
* faster test creation
* more time spent on actual test scenarios

It doesn't try to write your tests for you.

**It gets you to the point where you can write them.**

---

## What `mock-it-all` does — and doesn't do

`mock-it-all` is deliberately focused.

It doesn't try to guess what your class should do.
It doesn't invent assertions.
It doesn't decide which behavior matters.

It handles the mechanical part:

```text
Your production class
  ↓
Constructor dependencies
  ↓
Mocks
  ↓
Test class
```

**You stay in control of the test.**

---

## CLI-first

When you're already working in your terminal, generating a test shouldn't require leaving your workflow.

Give `mock-it-all` the class you want to test and let it generate the starting point.

```shell
php ./vendor/bin/mock-it-all create-test-stub-with-mocks --fqcn="PatrickMaynard\MockItAll\Stubs\President" --test-folder="tests/Unit"
```

✓ Inspecting constructor  
✓ Creating mocks  
✓ Wiring dependencies  
✓ Generating test class

## Test created successfully.

From there, you take over.

---

## Interactive when you want it

Not every workflow needs to be a one-liner.

If you'd rather have a guided experience, `mock-it-all` also provides an interactive assistant that helps you through the process.

**Fast when you know what you want.
Guided when you don't.**

---

## Get started

Install `mock-it-all` in your Composer project and turn your next constructor-heavy test setup into a one-command task.

```bash
composer require --dev mock-it-all
```

Then give it a class.

```text
Your class → mock-it-all → your test
```

That's it.

---

## Stop writing test plumbing

You've already written the same mock setup before.

Probably more than once.

Let `mock-it-all` write it for you.

**Give it a class. Get a test.**

---

## About the author

**Patrick is a software developer, freelance journalist, and English teacher based in Slovakia.**

His software development work focuses on **PHP, Symfony, and Laravel**, with a particular interest in building small, practical tools that remove repetitive work and solve everyday problems for developers.

He is the author of **[`mock-it-all`](#)** and **[`audit-class-generator`](https://github.com/patrickmaynard/audit-class-generator)**.

**`audit-class-generator`** helps developers navigate Twig template hierarchies by adding unique audit classes to rendered HTML elements. This makes it easy to identify the exact template responsible for an element directly from the browser.

[Website](https://home.patrickmaynard.com) · [LinkedIn](https://www.linkedin.com/in/patrick-maynard-9346937/)

*Native English · German · Czech · Slovak*
