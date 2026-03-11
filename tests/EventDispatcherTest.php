<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Utils\EventDispatcher;

/**
 * @covers \App\Utils\EventDispatcher
 */
class EventDispatcherTest extends TestCase
{
    protected function setUp(): void
    {
        EventDispatcher::flush();
    }

    protected function tearDown(): void
    {
        EventDispatcher::flush();
    }

    public function test_listen_and_dispatch_fires_listener(): void
    {
        $fired  = false;
        EventDispatcher::listen('user.created', function () use (&$fired) {
            $fired = true;
        });

        EventDispatcher::dispatch('user.created');
        $this->assertTrue($fired);
    }

    public function test_dispatch_passes_payload(): void
    {
        $received = null;
        EventDispatcher::listen('order.placed', function ($payload) use (&$received) {
            $received = $payload;
        });

        EventDispatcher::dispatch('order.placed', ['id' => 42]);
        $this->assertSame(['id' => 42], $received);
    }

    public function test_dispatch_collects_results(): void
    {
        EventDispatcher::listen('ping', fn() => 'pong1');
        EventDispatcher::listen('ping', fn() => 'pong2');

        $results = EventDispatcher::dispatch('ping');
        $this->assertSame(['pong1', 'pong2'], $results);
    }

    public function test_wildcard_listener_fires_on_matching_event(): void
    {
        $count = 0;
        EventDispatcher::listen('user.*', function () use (&$count) {
            $count++;
        });

        EventDispatcher::dispatch('user.created');
        EventDispatcher::dispatch('user.updated');
        EventDispatcher::dispatch('order.placed'); // should not fire

        $this->assertSame(2, $count);
    }

    public function test_once_listener_fires_only_once(): void
    {
        $count = 0;
        EventDispatcher::once('payment.processed', function () use (&$count) {
            $count++;
        });

        EventDispatcher::dispatch('payment.processed');
        EventDispatcher::dispatch('payment.processed');
        EventDispatcher::dispatch('payment.processed');

        $this->assertSame(1, $count);
    }

    public function test_has_listeners_returns_true_when_registered(): void
    {
        EventDispatcher::listen('test.event', fn() => null);
        $this->assertTrue(EventDispatcher::hasListeners('test.event'));
    }

    public function test_has_listeners_returns_false_when_none(): void
    {
        $this->assertFalse(EventDispatcher::hasListeners('nonexistent.event'));
    }

    public function test_flush_removes_all_listeners(): void
    {
        EventDispatcher::listen('a', fn() => null);
        EventDispatcher::listen('b', fn() => null);

        EventDispatcher::flush();

        $this->assertFalse(EventDispatcher::hasListeners('a'));
        $this->assertFalse(EventDispatcher::hasListeners('b'));
    }

    public function test_flush_single_event_removes_only_that_event(): void
    {
        EventDispatcher::listen('a', fn() => null);
        EventDispatcher::listen('b', fn() => null);

        EventDispatcher::flush('a');

        $this->assertFalse(EventDispatcher::hasListeners('a'));
        $this->assertTrue(EventDispatcher::hasListeners('b'));
    }

    public function test_no_event_fired_returns_empty_array(): void
    {
        $results = EventDispatcher::dispatch('no.listeners.here');
        $this->assertSame([], $results);
    }
}
