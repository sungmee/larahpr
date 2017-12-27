<?php

namespace Sungmee\Larahpr;

class Helper
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

    public function __call(string $name, $args = null) {
        $class = strlen($name) <= 2 ? strtoupper($name) : studly_case($name);
        $class = "Sungmee\\Larahpr\\Classes\\$class";
        return new $class($args);
    }

    /**
     * 获取特定的 meta 值
     *
     * @param   string  $key   metas 表的 key
     * @param   object  $metas 默认为当前类中的 metas 对象。
     * @return  string  $value meta 值
     */
    public function pluckMeta(string $key, $metas = null)
    {
        $metas = $metas ? $metas : $this->metas;

        $meta = $metas->filter(function ($item) use ($key) {
            return $item->key == $key;
        })->first();

        return $meta ? $meta->value : null;
	}
}