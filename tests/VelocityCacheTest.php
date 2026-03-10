<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Utils\VelocityCache;

/**
 * @covers \App\Utils\VelocityCache
 */
class VelocityCacheTest extends TestCase
{
    private VelocityCache $cache;

    protected function setUp(): void
    {
        // Use a temp file for testing
        putenv('CACHE_ENABLED=true');
        putenv('CACHE_LOCATION=' . sys_get_temp_dir() . '/velocity_test_' . uniqid() . '.db');
        $this->cache = VelocityCache::getInstance();
        $this->cache->flush();
    }

    protected function tearDown(): void
    {
        $this->cache->flush();
    }

    public function test_put_and_get_value(): void
    {
        $this->cache->put('test.key', 'hello world', 60);
        $this->assertSame('hello world', $this->cache->get('test.key'));
    }

    public function test_get_returns_default_when_key_missing(): void
    {
        $result = $this->cache->get('missing.key', 'default');
        $this->assertSame('default', $result);
    }

    public function test_forget_removes_key(): void
    {
        $this->cache->put('delete.me', 'value', 60);
        $this->cache->forget('delete.me');
        $this->assertNull($this->cache->get('delete.me'));
    }

    public function test_flush_removes_all_entries(): void
    {
        $this->cache->put('a', 1, 60);
        $this->cache->put('b', 2, 60);
        $this->cache->flush();

        $this->assertNull($this->cache->get('a'));
        $this->assertNull($this->cache->get('b'));
    }

    public function test_put_stores_arrays(): void
    {
        $data = ['id' => 1, 'name' => 'Alice', 'roles' => ['admin', 'editor']];
        $this->cache->put('user.1', $data, 60);
        $this->assertSame($data, $this->cache->get('user.1'));
    }

    public function test_stats_returns_correct_counts(): void
    {
        $this->cache->put('x', 1, 60);
        $this->cache->put('y', 2, 60);

        $stats = $this->cache->getStats();

        $this->assertTrue($stats['enabled']);
        $this->assertGreaterThanOrEqual(2, $stats['active_entries']);
    }

    public function test_generate_key_includes_method_and_uri(): void
    {
        $key = $this->cache->generateKey('/users', 'GET');
        $this->assertStringContainsString('GET', $key);
        $this->assertStringContainsString('/users', $key);
    }

    public function test_forget_pattern_removes_matching_keys(): void
    {
        $this->cache->put('users.1', 'Alice', 60);
        $this->cache->put('users.2', 'Bob', 60);
        $this->cache->put('posts.1', 'Hello', 60);

        $this->cache->forgetPattern('users.*');

        $this->assertNull($this->cache->get('users.1'));
        $this->assertNull($this->cache->get('users.2'));
        $this->assertSame('Hello', $this->cache->get('posts.1'));
    }
}
