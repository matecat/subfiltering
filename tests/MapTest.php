<?php

namespace Matecat\SubFiltering\Tests;

use DomainException;
use Matecat\SubFiltering\Utils\Map;
use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * @author hashashiyyin domenico@translated.net / ostico@gmail.com
 * Date: 27/05/24
 * Time: 12:44
 *
 */
class MapTest extends TestCase
{

    /**
     * @test
     */
    public function put_test()
    {
        $m = Map::instance(['k' => 'v']);

        $oldValue = $m->get('k');
        $old_value = $m->put('k', 'pippo');

        $this->assertEquals($oldValue, $old_value);

        $new_value = $m->put('_not_existent_key', 'pippo');

        $this->assertNull($new_value);
    }

    /**
     * @test
     */
    public function test_for_each()
    {
        $i = 1;
        $m = Map::instance([
            "1" => "1",
            "2" => "2",
            "3" => "3",
            "4" => "4",
            "5" => "5",
            "6" => "6",
            "7" => "7"
        ]);

        $m->for_each(function ($k, $v) use (&$i) {
            $this->assertEquals($i, $k);
            $this->assertEquals($i, $v);
            $i++;
        });

        $this->assertEquals(8, $i);
    }

    /**
     * @test
     */
    public function test_get_or_default()
    {
        $m = Map::instance([
            "1" => "1",
            "2" => "2",
            "3" => "3",
            "4" => "4",
            "5" => "5",
            "6" => "6",
            "7" => "7"
        ]);

        $this->assertNull($m->get('not_exists'));
        $this->assertEquals("my_default", $m->getOrDefault("not_exists_too", "my_default"));
    }

    /**
     * @test
     */
    public function test_count_and_clear()
    {
        $m = Map::instance([
            "1" => "1",
            "2" => "2",
            "3" => "3",
            "4" => "4",
            "5" => "5",
            "6" => "6",
            "7" => "7"
        ]);

        $this->assertCount(7, $m);
        $m->clear();
        $this->assertCount(0, $m);
    }

    /**
     * @test
     */
    public function test_iterable()
    {
        $i = 1;
        $m = Map::instance([
            "1" => "1",
            "2" => "2",
            "3" => "3",
            "4" => "4",
            "5" => "5",
            "6" => "6",
            "7" => "7"
        ]);

        foreach ($m as $k => $v) {
            $this->assertEquals($i, $k);
            $this->assertEquals($i, $v);
            $i++;
        }

        $this->assertEquals(8, $i);
    }

    /**
     * @test
     */
    public function test_iterator()
    {
        $i = 1;
        $m = Map::instance([
            "1" => "1",
            "2" => "2",
            "3" => "3",
            "4" => "4",
            "5" => "5",
            "6" => "6",
            "7" => "7"
        ]);

        $iterator = $m->getIterator();

        while ($iterator->valid()) {
            $this->assertEquals($i, $iterator->current());
            $this->assertEquals($i, $iterator->current());
            $iterator->next();
            $i++;
        }
        $this->assertEquals(8, $i);
    }

    /**
     * @test
     */
    public function test_accesses()
    {
        $m = Map::instance([
            "1" => "1",
            "2" => "2",
            "3" => "3",
            "4" => "4",
            "5" => "5",
            "6" => "6",
            "7" => "7",
            "foo" => "bar",
        ]);

        $this->assertTrue($m->containsKey("foo"));
        $this->assertFalse($m->containsKey("ciao"));

        $this->assertTrue($m->containsValue("bar"));
        $this->assertFalse($m->containsKey("pippo"));

        $this->assertEquals('bar', $m["foo"]);
        $this->assertEquals('bar', $m->offsetGet('foo'));
        $this->assertNull($m['no-exists']);

        unset($m['foo']);
        $this->assertCount(7, $m);

        $m->offsetUnset('7');
        $this->assertCount(6, $m);

        $m->offsetSet('hello', 'world');
        $this->assertCount(7, $m);
        $this->assertTrue($m->offsetExists('hello'));
    }

    /**
     * @test
     */
    public function test_empty()
    {
        $m = Map::instance();
        $this->assertTrue($m->isEmpty());

        $this->assertFalse(empty($m));
    }

    /**
     * @test
     */
    public function test_put()
    {
        $m = Map::instance();
        $this->assertEquals(0, $m->size());

        $return = $m->put("key", 'value');
        $this->assertNull($return);
        $this->assertEquals(1, $m->size());

        $previousValue = $m->putIfAbsent("key", 'foobar');
        $this->assertEquals("value", $previousValue);
        $this->assertEquals("value", $m->get("key"));
        $this->assertEquals(1, $m->size());

        $return = $m->putIfAbsent("key2", 'value');
        $this->assertNull($return);
        $this->assertEquals(2, $m->size());
    }

    /**
     * @test
     */
    public function test_remove()
    {
        $m = Map::instance([
            "1" => "1",
            "2" => "2",
            "3" => "3",
            "4" => "4",
            "5" => "5",
            "6" => "6",
            "7" => "7",
            "foo" => "bar",
        ]);

        $this->assertCount(8, $m);

        $this->assertTrue($m->remove("foo"));
        $this->assertCount(7, $m);

        $this->assertFalse($m->remove("foo"));
        $this->assertCount(7, $m);
    }

    /**
     * @test
     */
    public function test_replace()
    {
        $m = Map::instance([
            "foo" => "bar",
        ]);

        $this->assertCount(1, $m);

        $return = $m->replace("key2", 'value');
        $this->assertNull($return);
        $this->assertEquals(1, $m->size());

        $return = $m->replace("foo", 'value');
        $this->assertNotNull($return);
        $this->assertEquals("bar", $return);
        $this->assertEquals(1, $m->size());
    }

    /**
     * @test
     */
    public function test_replace_if_equals()
    {
        $m = Map::instance([
            "foo" => "bar",
        ]);

        $this->assertCount(1, $m);

        $return = $m->replaceIfEquals("key2", 'value', 'buh!');
        $this->assertFalse($return);
        $this->assertEquals(1, $m->size());

        $return = $m->replaceIfEquals("foo", 'value', 'buh!');
        $this->assertFalse($return);
        $this->assertEquals(1, $m->size());

        $return = $m->replaceIfEquals("foo", 'new_value', 'bar');
        $this->assertTrue($return);
        $this->assertEquals(1, $m->size());
        $this->assertEquals("new_value", $m->get('foo'));
    }

    /**
     * @test
     */
    public function test_replaceAll()
    {
        $cube = function ($n) {
            return ($n * $n * $n);
        };

        $m = Map::instance([
            "1" => 1,
            "2" => 2,
            "3" => 3,
            "4" => 4,
            "5" => 5
        ]);

        $this->assertCount(5, $m);
        $m->replaceAll($cube);
        $this->assertCount(5, $m);

        $this->assertEquals(1, $m->get('1'));
        $this->assertEquals(8, $m->get('2'));
        $this->assertEquals(27, $m->get('3'));
        $this->assertEquals(64, $m->get('4'));
        $this->assertEquals(125, $m->get('5'));
    }

    /**
     * @test
     */
    public function test_keys_and_values()
    {
        $m = Map::instance([
            "1" => 1,
            "2" => 2,
            "3" => 3,
            "4" => 4,
            "5" => 5
        ]);

        $keys = ["1", "2", "3", "4", "5"];
        $values = [1, 2, 3, 4, 5,];
        $values_wrong_order = [1, 2, 4, 5, 3];

        $this->assertEquals($keys, $m->keySet());
        $this->assertEquals($values, $m->values());
        $this->assertNotEquals($values_wrong_order, $m->values());
    }

    /**
     * @test
     */
    public function test_compute()
    {
        $m = Map::instance([
            "1" => 1,
            "2" => 2,
            "3" => 3,
            "4" => 4,
            "5" => 5
        ]);

        //Attempts to compute a mapping for the specified key and its current mapped value
        // (or null if there is no current mapping)
        $res = $m->compute('foo', function ($k, $v) {
            $this->assertEquals('foo', $k);
            $this->assertNull($v);

            return "pippo";
        });
        $this->assertNull($res);

        //Attempts to compute a mapping for the specified key and its current mapped value
        $res = $m->compute('5', function ($k, $v) {
            $this->assertEquals('5', $k);
            $this->assertEquals(5, $v);

            return "pippo";
        });
        $this->assertEquals('pippo', $res);

        //If the function returns null, the mapping is removed (or remains absent if initially absent).
        $res = $m->compute('5', function ($k, $v) {
            $this->assertEquals('5', $k);
            $this->assertEquals('pippo', $v);

            return null;
        });
        $this->assertNull($res);
        $this->assertCount(4, $m);
    }

    /**
     * @test
     */
    public function compute_if_absent()
    {
        // If the specified key is not already associated with a value (or is mapped to null),
        // attempts to compute its value using the given mapping function and enters it into this map unless null.
        $m = Map::instance([
            "1" => 1,
            "2" => 2,
            "3" => 3,
            "4" => 4,
            "5" => 5
        ]);

        $res = $m->computeIfAbsent('foo', function ($k, $v) {
            $this->assertEquals('foo', $k);
            $this->assertNull($v);

            return "pippo";
        });
        //return the current (existing or computed) value associated with the specified key, or null if the computed value is null
        $this->assertCount(6, $m);
        $this->assertEquals('pippo', $res);

        // Not absent, no computation will be performed
        $res = $m->computeIfAbsent('5', function ($k, $v) {
            $this->assertFalse(true); // this will not be called

            return 'ciao';
        });
        $this->assertNull($res);


        //If the function returns null, no mapping is recorded.
        $res = $m->computeIfAbsent('XXXXX', function ($k, $v) {
            $this->assertTrue(true);

            return null;
        });
        $this->assertNull($res);
        $this->assertCount(6, $m);
    }

    /**
     * @test
     */
    public function compute_if_present()
    {
        //If the value for the specified key is present and non-null, attempts to compute a new mapping given the key and its current mapped value.
        $m = Map::instance([
            "1" => 1,
            "2" => 2,
            "3" => 3,
            "4" => 4,
            "5" => 5
        ]);

        $res = $m->computeIfPresent('5', function ($k, $v) {
            $this->assertEquals('5', $k);
            $this->assertEquals(5, $v);

            return "pippo";
        });
        // return the new value associated with the specified key, or null if none
        $this->assertCount(5, $m);
        $this->assertEquals('pippo', $res);


        //If the function returns null, the mapping is removed.
        $res = $m->computeIfPresent('5', function ($k, $v) {
            $this->assertEquals('5', $k);
            $this->assertEquals('pippo', $v);

            return null;
        });
        // return null and remove the mapping
        $this->assertCount(4, $m);
        $this->assertNull($res);


        // if no mapping is present, the callable function will not be invoked
        $res = $m->computeIfPresent('5', function ($k, $v) {
            $this->fail(); // this will not be called
        });
        $this->assertNull($res);
        $this->assertCount(4, $m);
    }

    /**
     * @test
     */
    public function test_compute_if_absent_when_value_is_null()
    {
        $m = Map::instance([
            "null_key" => null,
        ]);

        $res = $m->computeIfAbsent('null_key', function ($k, $v) {
            $this->assertEquals('null_key', $k);
            $this->assertNull($v);

            return 0;
        });

        $this->assertNull($res);
        $this->assertSame(0, $m->get('null_key'));
    }

    /**
     * @test
     */
    public function test_compute_if_present_skips_null_value()
    {
        $m = Map::instance([
            "null_key" => null,
        ]);

        $res = $m->computeIfPresent('null_key', function () {
            $this->assertFalse(true);

            return 'should_not_run';
        });

        $this->assertNull($res);
        $this->assertTrue($m->containsKey('null_key'));
        $this->assertNull($m->get('null_key'));
    }

    /**
     * @test
     */
    public function test_pu_all()
    {
        $m = Map::instance([
            "1" => 1,
            "2" => 2
        ]);
        $this->assertCount(2, $m);
        $m->putAll([
            "3" => 3,
            "4" => 4,
            "5" => 5
        ]);
        $this->assertCount(5, $m);
        $this->assertEquals(1, '1');
        $this->assertEquals(3, '3');
        $this->assertEquals(5, '5');
    }

    /**
     * @test
     */
    public function test_clone()
    {
        $m = Map::instance([
            "1" => 1,
            "2" => 2
        ]);

        $m1 = clone $m;

        $this->assertEquals($m, $m1);
        $this->assertNotSame($m, $m1);
    }

    /**
     * @test
     */
    public function no_map_exception()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid map provided');
        Map::instance([1, 2, 3]);
    }

    /**
     * @test
     */
    public function test_constructor_rejects_list_input()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid map provided');

        new Map([1, 2, 3]);
    }

    /**
     * @test
     */
    public function test_compute_if_present_updates_value_when_non_null()
    {
        $m = Map::instance([
            "k" => 10,
        ]);

        $res = $m->computeIfPresent('k', function ($k, $v) {
            $this->assertEquals('k', $k);
            $this->assertEquals(10, $v);

            return 20;
        });

        $this->assertSame(20, $res);
        $this->assertSame(20, $m->get('k'));
    }

    /**
     * @test
     */
    public function test_compute_if_present_removes_mapping_on_null_result()
    {
        $m = Map::instance([
            "k" => "v",
        ]);

        $res = $m->computeIfPresent('k', function ($k, $v) {
            $this->assertEquals('k', $k);
            $this->assertEquals('v', $v);

            return null;
        });

        $this->assertNull($res);
        $this->assertFalse($m->containsKey('k'));
    }
}
