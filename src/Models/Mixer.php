<?php

namespace Sungmee\Larahpr\Models;

use DB;
use Carbon\Carbon;

class Mixer extends Eloquent
{
    /**
     * 与模型关联的数据表
     *
     * @var string
     */
	protected $columnForSubTable = 'created_at';

    /**
     * 分表规则
     *
     * @var string
	 *
	 * 示例：
	 * Weekly（每周一次）  	=> YW
	 * Monthly（每月一次） 	=> Ym
	 * Quarterly（每季一次）	=> YQ // ceil((date('n'))/3) || $dt->quarter;
	 * Yearly（每年一次）		=> Y
     */
	protected $subTableSuffix = 'Ym';

    /**
     * 获取二元历史订单，可以通过链式操作，缩小订单范围。
     *
     * @date   2017-12-22 14:42:56
     * @param  array    $range      界定查询时间，可用链式操作替代 [start|2017-11-01, end|2017-11-30]
     * @param  int      $default    默认查询天数，优先使用 $range
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function mix($range = null, int $default = null)
    {
        $mix = true;

        if (is_array($range) && ! empty(array_filter($range))) {
            $start = Carbon::parse($range[0], $this->tz)->startOfDay();
            $end   = Carbon::parse($range[1], $this->tz)->endOfDay();
        } elseif (is_string($range) && strtotime($range) != false) {
            $start = Carbon::parse($range, $this->tz)->startOfDay();
            $end   = $start->copy()->endOfDay();
        } elseif ($default) {
            $end   = Carbon::today($this->tz);
            $start = $end->copy()->subDays($default - 1);
            $end   = $end->endOfDay();
        } else {
            $mix   = false;
        }

        if ($mix) {
            // 初始化查询集合
            $c = collect();

			$prefix = $this->tablePrefix;
			$suffix = $start->copy();

			// 循环添加每一张表的查询
			switch ($this->subTableSuffix) {
				case 'Y':
				$suffix->startOfYear();
				do {
					$c->push(DB::table("{$prefix}_{$suffix->format('Y')}"));
					$suffix->addYear();
				} while ($end->startOfYear()->diffInYears($suffix, false) <= 0);
				break;

				case 'Ym':
				$suffix->startOfMonth();
				do {
					$c->push(DB::table("{$prefix}_{$suffix->format('Ym')}"));
					$suffix->addMonth();
				} while ($end->startOfMonth()->diffInMonths($suffix, false) <= 0);
				break;

				case 'YW':
				$suffix->startOfWeek();
				do {
					$c->push(DB::table("{$prefix}_{$suffix->format('YW')}"));
					$suffix->addWeek();
				} while ($end->startOfWeek()->diffInWeeks($suffix, false) <= 0);
				break;

				case 'YQ':
				$year  = $start->copy()->format('Y');
				$month = $suffix->quarter * 3 - 2;
				$quart = Carbon::createFromDate($year, $month, 1, $this->tz);
				do {
					$suffix = $quart->copy()->format('Y') . $quart->copy()->quarter;
					$c->push(DB::table("{$prefix}_{$suffix}"));
					$quart->addMonths(3);
				} while ($end->startOfMonth()->diffInMonths($quart, false) <= 0);
				break;
			}

            // 出列一张表作为 union 的开始
            $q = $c->shift();

            // 循环剩下的表并添加 union
            $c->each(function ($item) use ($q) {
                $q->unionAll($item);
            });

			// 设置临时表的名称，添加临时表，顺序不能反过来，否则用关联约束会找不到表
            return with($this)->setTable('mixed')
                // 添加临时表
                ->from(DB::raw("({$q->toSql()}) as mixed"))
                // 合并查询条件
                ->mergeBindings($q)
                ->whereBetween($this->$columnForSubTable, [
                    $start, $end
                ]);
        }

        return $this;
    }

    public static function mixer($range = null, int $days = null)
    {
        return (new self)->mix($range, $days);
    }

    public static function whereBetween($column, array $values, $boolean = 'and', $not = false)
    {
        return $column == $this->columnForSubTable
            ? (new self)->mix($values)
            : parent::getQuery()->whereBetween($column, $values, $boolean, $not);
	}
}