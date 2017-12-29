<?php

namespace Sungmee\Larahpr;

class HPR
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

    public function __call($name, $args) {
        $field = preg_match('/^get(\w+)/', $name, $matches);
        if ($field && $matches[1]) {
            return $this->{camel_case($matches[1])};
        }

        $field = preg_match('/^set(\w+)/', $name, $matches);
        if ($field && $matches[1]) {
            $this->{camel_case($matches[1])} = $args[0];
            return $this;
		}

        $name = strlen($name) <= 2 ? strtoupper($name) : studly_case($name);
        $name = "Sungmee\\Larahpr\\Helpers\\$name";
        return new $name($args);
    }

    /**
     * 获取特定的 meta 值
     *
     * @param   string  $key   metas 表的 key
     * @param   object  $metas 默认为当前类中的 metas 对象。
     * @return  string  $value meta 值
     */
    public function pluckMeta(string $key, $metas)
    {
        if (empty($metas))
            return null;
        elseif (is_string($metas) || is_numeric($metas))
            return $metas;
        elseif (is_array($metas))
            $metas = collect($metas);

        $meta = $metas->filter(function ($item) use ($key) {
            return $item->key == $key;
        })->first();

        return $meta ? $meta->value : null;
	}
}