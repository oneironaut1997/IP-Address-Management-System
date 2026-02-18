<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base Test Case for Gateway Service Tests
 *
 * Provides database refresh functionality for tests.
 */
abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
}
