<?php

namespace Sungmee\Larahpr\Helpers;

use Sungmee\Larahpr\HPR;
use Illuminate\Support\Facades\Cache;

class FX extends HPR
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
     * 获取最新汇率
     *
     * @param   string  $base       汇率基础货币
     * @param   string  $symbols    需要查询的货币种类，多个种类用英文逗号隔开
     * @return  object  stdClass Object ( [base] => USD [date] => 2017-09-25 [rates] => stdClass Object ( [CNY] => 6.6233 ) )
     */
    public function fixer($base = 'USD', $symbols = 'CNY', $minutes = 60)
    {
        return Cache::tags(['fixer', $base])
            ->remember($symbols, $minutes, function() use($base, $symbols) {
                $base_url = "https://api.fixer.io/latest";
                $url      = "{$base_url}?base={$base}&symbols={$symbols}";
                $ch       = curl_init();

                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                $json = curl_exec($ch);
                curl_close($ch);

                return json_decode($json);
        });
	}

    /**
     * 美元转人民币
     *
     * @param   float   $money  需要转换的金额
     * @return  float           转换后的金额
     */
    public function usd2cny(float $money)
    {
        $rate = $this->fixer()->rates->CNY;
        return $money * $rate;
    }
}