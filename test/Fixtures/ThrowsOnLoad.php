<?php declare(strict_types=1);

namespace PatrickMaynard\MockItAll\Tests\Fixtures;

/**
 * Loading this file always fails, standing in for real-world vendor classes
 * (e.g. Symfony's AsciiSlugger, when symfony/translation-contracts isn't
 * installed) that throw a non-TypeError Throwable at load time rather than
 * simply not existing.
 */
throw new \LogicException('Simulated load-time failure: an optional dependency is missing.');
