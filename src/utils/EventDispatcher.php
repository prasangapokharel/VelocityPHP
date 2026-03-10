<?php
/**
 * VelocityPHP Event System
 * Lightweight event dispatcher for decoupled application logic
 *
 * @package VelocityPHP
 * @version 1.1.0
 */

namespace App\Utils;

class EventDispatcher
{
    /** @var array<string, callable[]> */
    private static $listeners = [];

    /** @var array<string, bool> */
    private static $wildcardListeners = [];

    /**
     * Register an event listener.
     *
     * @param string   $event    Event name (supports '*' wildcard, e.g. 'user.*')
     * @param callable $listener Callback to invoke when the event fires
     * @return void
     */
    public static function listen(string $event, callable $listener): void
    {
        self::$listeners[$event][] = $listener;

        if (strpos($event, '*') !== false) {
            self::$wildcardListeners[$event] = true;
        }
    }

    /**
     * Dispatch an event to all registered listeners.
     *
     * @param string $event   The event name
     * @param mixed  $payload Data to pass to listeners
     * @return array Results returned by each listener
     */
    public static function dispatch(string $event, $payload = null): array
    {
        $results = [];

        // Direct listeners
        foreach (self::$listeners[$event] ?? [] as $listener) {
            $result = $listener($payload, $event);
            if ($result !== null) {
                $results[] = $result;
            }
        }

        // Wildcard listeners
        foreach (array_keys(self::$wildcardListeners) as $pattern) {
            if ($pattern !== $event && self::matchesWildcard($pattern, $event)) {
                foreach (self::$listeners[$pattern] ?? [] as $listener) {
                    $result = $listener($payload, $event);
                    if ($result !== null) {
                        $results[] = $result;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Register a one-time listener that fires only on the first invocation.
     *
     * @param string   $event
     * @param callable $listener
     * @return void
     */
    public static function once(string $event, callable $listener): void
    {
        $wrapper = function ($payload, $ev) use ($listener) {
            static $called = false;
            if (!$called) {
                $called = true;
                return $listener($payload, $ev);
            }
            return null;
        };

        self::listen($event, $wrapper);
    }

    /**
     * Remove a specific listener for an event.
     *
     * @param string   $event
     * @param callable $listener
     * @return void
     */
    public static function off(string $event, callable $listener): void
    {
        if (!isset(self::$listeners[$event])) {
            return;
        }

        self::$listeners[$event] = array_values(
            array_filter(self::$listeners[$event], static function ($l) use ($listener) {
                return $l !== $listener;
            })
        );
    }

    /**
     * Remove all listeners for a given event (or all events if null).
     *
     * @param string|null $event
     * @return void
     */
    public static function flush(?string $event = null): void
    {
        if ($event === null) {
            self::$listeners        = [];
            self::$wildcardListeners = [];
        } else {
            unset(self::$listeners[$event], self::$wildcardListeners[$event]);
        }
    }

    /**
     * Check whether any listener is registered for an event.
     *
     * @param string $event
     * @return bool
     */
    public static function hasListeners(string $event): bool
    {
        return !empty(self::$listeners[$event]);
    }

    /**
     * Match a wildcard pattern against an event name.
     *
     * @param string $pattern e.g. 'user.*'
     * @param string $event   e.g. 'user.created'
     * @return bool
     */
    private static function matchesWildcard(string $pattern, string $event): bool
    {
        $regex = '/^' . str_replace(
            ['\\*', '\\?'],
            ['[^.]+', '.'],
            preg_quote($pattern, '/')
        ) . '$/';

        return (bool) preg_match($regex, $event);
    }

    /**
     * Get all registered event names.
     *
     * @return string[]
     */
    public static function getRegisteredEvents(): array
    {
        return array_keys(self::$listeners);
    }

    /**
     * Dispatch an event and halt if any listener returns false.
     *
     * @param string $event
     * @param mixed  $payload
     * @return bool True if all listeners passed, false if one returned false
     */
    public static function until(string $event, $payload = null): bool
    {
        foreach (self::$listeners[$event] ?? [] as $listener) {
            if ($listener($payload, $event) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Subscribe a class-based event subscriber.
     * The subscriber class must implement a subscribe(EventDispatcher $dispatcher) method.
     *
     * @param object $subscriber
     * @return void
     */
    public static function subscribe(object $subscriber): void
    {
        if (method_exists($subscriber, 'subscribe')) {
            $subscriber->subscribe();
        }
    }
}
