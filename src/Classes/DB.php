<?php

namespace Sungmee\Larahpr\Classes;

use Illuminate\Support\Facades\DB as D;

class DB
{
    /**
     * 配置
     *
     * @var array
     */
    protected $conf;

	/**
     * 创建一个新实例。
     *
     * @return void
     */
    public function __construct()
    {
		$this->conf = config('larahpr');
    }

    /**
     * 根据单表名向数据库插入数据
     *
     * @param string    $table  数据表名
     * @param array     $data   插入的行数据集合
     * @return bool             插入成功返回 TRUE，否则 FALSE
     */
    public function insertWithTable(string $table, array $data)
    {
        foreach (array_chunk($data, 3000) as $chunk) {
            if (! D::table($table)->insert($chunk)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 根据多表名分表向数据库插入数据
     *
     * @param  array    $data 插入的历史订单数据集合，['table' => [['ticket' => 1...], ['ticket' => 2...]]]
     * @return bool           插入成功返回 TRUE，否则 FALSE
     */
    public function insertWithTables(array $datas)
    {
        foreach ($datas as $table => $data) {
            if (! $this->insertWithTable($table, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 查询混合器
     *
     * @param  int       $start  起始表后缀，如：201712
     * @param  int       $end    截止表后缀
     * @param  string    $table  查询表前缀
     * @return object
     */
    public function mixer(int $start, int $end, string $table)
    {
        $tmp = collect();

        $start = $start < $this->conf['db_start']
            ? $this->conf['db_start']
            : $start;

        do {
            $tmp->push(D::table("{$table}_{$start}"));
            $start++;
        } while ($start <= $end);

        $query = $tmp->shift();

        $tmp->each(function ($item) use ($query) {
            $query->union($item);
        });

        $table = str_plural($table);

        return D::table(D::raw("({$query->toSql()}) as $table"))
            ->mergeBindings($query);
    }

    /**
     * 更新多行数据，不同行被更新数据须一致
     *
     * @param string    $table  数据表名
     * @param array     $data   更新的行数据
     * @param array     $where  更新的行的条件
     * @param string    $key    更新的行的条件的列名（键）
     * @return bool             插入成功返回 TRUE，否则 FALSE
     */
    public function updateRows(string $table, array $data, array $where, string $key = 'ticket')
    {
        foreach (array_chunk($where, 3000) as $chunk) {
            $result = D::table($table)
                ->whereIn($key, $chunk)
                ->update($data);

            if (! $result) {
                return false;
            }
        }

        return true;
    }

	/**
     * 多字段多行更新
     *
     * @param  string   $table      表名
     * @param  array    $values     需要更新的数据
     * @param  string   $index      索引字段
     * @return int      rows        更新行数
     *
     * 示例
     *
     * $table  = 'users';
     * $values = [
     *      [
     *          'id'       => 1,
     *          'status'   => 'active',
     *          'nickname' => '张三'
     *      ],
     *      [
     *          'id'       => 5,
     *          'status'   => 'deactive',
     *          'username' => 'lisi'
     *      ],
     * ];
     *
     * $index  = 'id';
     *
     */
    public function updateBatch(string $table, array $values, string $index)
    {
        if(empty($values))
            return false;

        $final  = [];
        $ids    = [];
        foreach ($values as $key => $val)
        {
            $ids[] = $val[$index];
            foreach (array_keys($val) as $field)
            {
                if ($field !== $index)
                {
                    $final[$field][] = 'WHEN `'. $index .'` = "' . $val[$index] . '" THEN "' . $val[$field] . '" ';
                }
            }
        }

        $cases = '';
        foreach ($final as $k => $v)
        {
            $cases .= $k.' = (CASE '. implode("\n", $v) . "\n"
                            . 'ELSE '.$k.' END), ';
        }

        $query = 'UPDATE ' . $table . ' SET '. substr($cases, 0, -2) . ' WHERE ' . $index . ' IN('.implode(',', $ids).')';
        return D::statement($query);
    }
}