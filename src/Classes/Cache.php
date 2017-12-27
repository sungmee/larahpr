<?php

namespace Sungmee\Larahpr\Classes;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache as C;

class Cache
{
	/**
     * 创建一个新实例。
     *
     * @return void
     */
    public function __construct()
    {
		//
    }

    /**
     * 根据方法所在的命名空间、类和参数等信息，生成缓存的标签和键
     *
     * @param   string  $affix  附加的缓存键
     * @return  array   $keys   ['tags' => [], 'key' => 'string']
     */
    public function key(string $affix = '')
    {
        $trac = debug_backtrace()[1];
        $tags = [$trac['class'], $trac['function']];
        $keys = array_map(function ($item) {
            if (is_object($item)) {
                $item = join('|', $item->all());
            } elseif (is_array($item)) {
                $item = join('|', $item);
            }
            return $item;
        }, $trac['args']);
        $keys = array_merge([$trac['function'], $affix], $keys);
        $keys = array_filter($keys);

        return ['tags' => $tags, 'key' => md5(join('|', $keys))];
    }

    /**
     * 根据参数的标签和键，返回缓存数据
     *
     * @param   array   $keys   缓存的标签和键的数组 ['tags' => [], 'key' => 'string']
     * @return  Cache
     */
    public function get(array $keys)
    {
        return C::tags($keys['tags'])->get($keys['key']);
    }

    /**
     * 根据特定参数，将数据缓存——如果截止日期是今天、或未设置，默认缓存 10 分钟，否则 7 天。
     *
     * @param   array               $keys       缓存的标签和键的数组['tags' => [], 'key' => 'string']
     * @param   string|array|object $data       需要缓存的数据
     * @param   string              $end        缓存数据的时间段的截止时间
     * @param   int                 $minutes    默认缓存时间
     * @return  bool
     */
    public function put(array $keys, $data, string $end = null, int $minutes = 10)
    {
        $tzn = config('mt4.timezone');
        $end = $end ? Carbon::parse($end, $tzn) : null;
        $tod = empty($end) || Carbon::today($tzn)->diffInDays($end) == 0;
        $exp = $tod ? $minutes : Carbon::now($tzn)->addDays(7);
        return C::tags($keys['tags'])->put($keys['key'], $data, $exp);
    }
}