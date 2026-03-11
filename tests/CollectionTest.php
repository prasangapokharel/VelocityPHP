<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Utils\Collection;

/**
 * @covers \App\Utils\Collection
 */
class CollectionTest extends TestCase
{
    public function test_make_creates_collection(): void
    {
        $c = Collection::make([1, 2, 3]);
        $this->assertInstanceOf(Collection::class, $c);
        $this->assertCount(3, $c);
    }

    public function test_filter_returns_matching_items(): void
    {
        $c = Collection::make([1, 2, 3, 4, 5])->filter(fn($n) => $n > 3);
        $this->assertSame([4, 5], $c->toArray());
    }

    public function test_map_transforms_items(): void
    {
        $c = Collection::make([1, 2, 3])->map(fn($n) => $n * 2);
        $this->assertSame([2, 4, 6], $c->toArray());
    }

    public function test_where_filters_by_key_value(): void
    {
        $data = [
            ['name' => 'Alice', 'role' => 'admin'],
            ['name' => 'Bob',   'role' => 'user'],
            ['name' => 'Carol', 'role' => 'admin'],
        ];
        $admins = Collection::make($data)->where('role', 'admin');
        $this->assertCount(2, $admins);
    }

    public function test_first_returns_first_item(): void
    {
        $c = Collection::make([10, 20, 30]);
        $this->assertSame(10, $c->first());
    }

    public function test_first_with_callback(): void
    {
        $c = Collection::make([1, 2, 3, 4]);
        $this->assertSame(3, $c->first(fn($n) => $n > 2));
    }

    public function test_last_returns_last_item(): void
    {
        $c = Collection::make([10, 20, 30]);
        $this->assertSame(30, $c->last());
    }

    public function test_pluck_extracts_key(): void
    {
        $data = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];
        $names = Collection::make($data)->pluck('name');
        $this->assertSame(['Alice', 'Bob'], $names->toArray());
    }

    public function test_unique_removes_duplicates(): void
    {
        $c = Collection::make([1, 2, 2, 3, 3, 3]);
        $this->assertSame([1, 2, 3], $c->unique()->toArray());
    }

    public function test_take_limits_items(): void
    {
        $c = Collection::make([1, 2, 3, 4, 5])->take(3);
        $this->assertSame([1, 2, 3], $c->toArray());
    }

    public function test_skip_offsets_items(): void
    {
        $c = Collection::make([1, 2, 3, 4, 5])->skip(2);
        $this->assertSame([3, 4, 5], $c->toArray());
    }

    public function test_chunk_splits_into_pieces(): void
    {
        $c = Collection::make([1, 2, 3, 4, 5])->chunk(2);
        $this->assertCount(3, $c);
    }

    public function test_is_empty_returns_true_for_empty(): void
    {
        $c = Collection::make([]);
        $this->assertTrue($c->isEmpty());
        $this->assertFalse($c->isNotEmpty());
    }

    public function test_is_not_empty_returns_true_for_items(): void
    {
        $c = Collection::make([1]);
        $this->assertTrue($c->isNotEmpty());
        $this->assertFalse($c->isEmpty());
    }

    public function test_reduce_sums_values(): void
    {
        $sum = Collection::make([1, 2, 3, 4, 5])->reduce(fn($carry, $item) => $carry + $item, 0);
        $this->assertSame(15, $sum);
    }

    public function test_sort_by_key(): void
    {
        $data = [
            ['name' => 'Charlie'],
            ['name' => 'Alice'],
            ['name' => 'Bob'],
        ];
        $sorted = Collection::make($data)->sortBy('name')->pluck('name')->toArray();
        $this->assertSame(['Alice', 'Bob', 'Charlie'], $sorted);
    }

    public function test_to_json_encodes_correctly(): void
    {
        $json = Collection::make([1, 2, 3])->toJson();
        $this->assertSame('[1,2,3]', $json);
    }

    public function test_array_access_works(): void
    {
        $c = Collection::make(['a', 'b', 'c']);
        $this->assertSame('b', $c[1]);
        $this->assertTrue(isset($c[0]));
        $this->assertFalse(isset($c[99]));
    }

    public function test_iterator_traversal(): void
    {
        $c = Collection::make([10, 20, 30]);
        $result = [];
        foreach ($c as $item) {
            $result[] = $item;
        }
        $this->assertSame([10, 20, 30], $result);
    }
}
