<?php

namespace WebmanTech\LaravelCache\Tests\Facades;

use Illuminate\Cache\ApcStore;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockTimeoutException;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemPoolInterface;
use Psr\SimpleCache\CacheInterface;
use WebmanTech\LaravelCache\Facades\Cache;

/**
 * https://laravel.com/docs/10.x/cache
 */
class CacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Cache::flush();
    }

    public function testInstance()
    {
        $this->assertInstanceOf(CacheManager::class, Cache::instance());
        $this->assertInstanceOf(CacheInterface::class, Cache::psr16());
        $this->assertInstanceOf(CacheItemPoolInterface::class, Cache::psr6());
    }

    public function testStore()
    {
        $this->assertInstanceOf(ApcStore::class, Cache::store('apc')->getStore());
    }

    public function testNormalFn()
    {
        // get 获取数据
        $this->assertEquals(null, Cache::get('not_exist'));
        $this->assertEquals('default', Cache::get('not_exist', 'default'));
        $this->assertEquals('default', Cache::get('not_exist', function () {
            return 'default';
        }));

        // add 添加数据，如果存在时不会覆盖
        $this->assertTrue(Cache::add('add_key', 1));
        $this->assertEquals(1, Cache::get('add_key'));
        $this->assertFalse(Cache::add('add_key', 1));
        $this->assertEquals(1, Cache::get('add_key'));

        // put 添加数据，如果存在时会覆盖
        $this->assertTrue(Cache::put('new_key', 1));
        $this->assertEquals(1, Cache::get('new_key', 222));
        $this->assertTrue(Cache::put('new_key', 2));
        $this->assertEquals(2, Cache::get('new_key'));

        // has 判断是否存在
        $this->assertFalse(Cache::has('not_exist'));
        $this->assertTrue(Cache::has('new_key'));

        // forget 删除数据
        Cache::add('forget_key', 1);
        $this->assertTrue(Cache::forget('forget_key'));
        $this->assertFalse(Cache::forget('not_exist'));

        // put 有效期为0或负数时，也是删除数据
        Cache::add('put_delete_key', 1);
        $this->assertTrue(Cache::put('put_delete_key', 1, 0));
        $this->assertFalse(Cache::has('put_delete_key'));
        Cache::add('put_delete_key', 1);
        $this->assertTrue(Cache::put('put_delete_key', 1, -5));
        $this->assertFalse(Cache::has('put_delete_key'));

        // increment 自增
        $this->assertEquals(1, Cache::increment('increment_key'));
        $this->assertEquals(2, Cache::increment('increment_key'));
        Cache::add('increment_key2', 5);
        $this->assertEquals(6, Cache::increment('increment_key2'));
        Cache::add('increment_key3', '6');
        $this->assertEquals(7, Cache::increment('increment_key3')); // 递增一个字符串类型的数字
        Cache::add('increment_key4', 'string');
        $this->assertContains(Cache::increment('increment_key4'), [1, false]); // 递增一个字符串，不同的store返回值不一样，可能 false，可能 1
        Cache::add('increment_key5', 1);
        $this->assertEquals(10, Cache::increment('increment_key5', 9));

        // decrement 自减
        $this->assertEquals(-1, Cache::decrement('decrement_key'));
        Cache::add('decrement_key2', 5);
        $this->assertEquals(4, Cache::decrement('decrement_key2'));

        // remember 记住数据，如果已经存在，则直接返回，否则执行 callback 后缓存并返回
        $this->assertEquals('remember_value', Cache::remember('remember_key', 60, function () {
            return 'remember_value';
        }));
        $this->assertEquals('remember_value', Cache::remember('remember_key', 60, function () {
            return 'remember_value2';
        }));
        $this->assertEquals('remember_value', Cache::get('remember_key'));
        $this->assertEquals('remember_value2', Cache::rememberForever('remember_key2', function () {
            return 'remember_value2';
        }));
        $this->assertEquals('remember_value2', Cache::get('remember_key2'));

        // pull 获取数据并删除
        $this->assertNull(Cache::pull('not_exist'));
        Cache::add('pull_key', 1);
        $this->assertEquals(1, Cache::pull('pull_key'));
        $this->assertFalse(Cache::has('pull_key'));

        // forever 永久缓存
        $this->assertTrue(Cache::forever('forever_key', 1));
        $this->assertEquals(1, Cache::get('forever_key'));
    }

    public function testTTL()
    {
        Cache::add('ttl_key', 1, 2);
        $this->assertTrue(Cache::has('ttl_key'));
        sleep(2);
        $this->assertFalse(Cache::has('ttl_key'));
    }

    public function testTag()
    {
        if (!Cache::supportsTags()) {
            // 对于不支持的不测试
            $this->markTestSkipped('This store not support tags');
        }

        // 将数据放到某 tag 下
        Cache::tags('tag1')->put('tag_key', 1);
        $this->assertTrue(Cache::tags('tag1')->has('tag_key'));

        // 清空 tag，不清其他数据
        Cache::add('global_key', 1);
        Cache::tags('tag1')->add('tag_key', 1);
        Cache::tags('tag1')->flush();
        $this->assertTrue(Cache::has('global_key'));
        $this->assertFalse(Cache::tags('tag1')->has('tag_key'));

        // 多tag逻辑
        // 数据
        Cache::tags(['tag1', 'tag2'])->add('tag_key', 1);
        Cache::tags(['tag1', 'tag3'])->add('tag_key2', 1);
        // 必须通过两个 tag 才能查到，单个 tag 查不到数据
        $this->assertTrue(Cache::tags(['tag1', 'tag2'])->has('tag_key'));
        $this->assertFalse(Cache::tags('tag1')->has('tag_key'));
        // 清空 tag1, tag3，将清空 tag_key
        Cache::tags(['tag1', 'tag3'])->flush();
        $this->assertFalse(Cache::tags(['tag1', 'tag2'])->has('tag_key'));
        // 数据
        Cache::tags(['tag1', 'tag2'])->add('tag_key', 1);
        Cache::tags(['tag1', 'tag3'])->add('tag_key2', 1);
        // 清空 tag3，将保留 tag_key
        Cache::tags('tag3')->flush();
        $this->assertTrue(Cache::tags(['tag1', 'tag2'])->has('tag_key'));
    }

    public function testLock()
    {
        $this->assertInstanceOf(Lock::class, Cache::lock('lock_key'));

        // get release
        $lock = Cache::lock('lock_key');
        $this->assertTrue($lock->get());
        $this->assertFalse($lock->get()); // 重复获取锁会失败
        $this->assertTrue($lock->release());
        $this->assertFalse($lock->release()); // 重复释放锁会失败
        $this->assertTrue($lock->get()); // 同一个锁释放后不能再用

        // get callback
        $lock = Cache::lock('lock_key2');
        $this->assertEquals('xxx', $lock->get(function () {
            // 通过回调函数处理当获取到锁的时候的逻辑
            return 'xxx';
        }));

        // block
        $lock = Cache::lock('lock_key3', 2);
        $this->assertTrue($lock->get());
        $lock2 = Cache::lock('lock_key3', 2);
        $this->assertFalse($lock2->get()); // 2秒内获取不到锁
        try {
            $lock2->block(2);
        } catch (\Throwable $e) {
            $this->assertInstanceOf(LockTimeoutException::class, $e);
        }

        // restore
        $lock = Cache::lock('lock_key4', 2);
        $owner = $lock->owner();
        $this->assertIsString($owner);
        $lock2 = Cache::restoreLock('lock_key4', $owner);
        $this->assertEquals($lock2->owner(), $owner);
        $this->assertTrue($lock->get());
        $this->assertTrue($lock2->release());
    }
}
