<?php

namespace Sungmee\Larahpr\Classes;

class Hash
{
    /**
     * 配置
     *
     * @var array
     */
    protected $conf;

    /**
     * 加密后字符串长度
     *
     * @var integer
     */
    private $length;

    /**
     * 加密盐值
     *
     * @var number
     */
    private $salt;

    /**
     * 字典 - a-z,A-Z,0-9 62个字符打乱后的字符串
     *
     * @var string
     */
    private $dictionary;

    /**
     * 标记 - 取前字典的 M(数字最大的位数)位作为标记长度字符串
     *
     * @var string
     */
    private $flag;

    /**
     * 替身 - 取字典的第 M+1 到第 M+10 位为数字替换字符串
     *
     * @var string
     */
    private $substitute;

    /**
     * 补缺 - 取字典取掉 $flag 和 $substitute 后剩下的字符串作为候补字符串
     *
     * @var string
     */
    private $alternate;

    /**
     * 构造一个 Hash ID 的 函数
     *
     * @date   2017/07/05 11:27:17
     * @param  integer  $length     加密后字符串长度
     * @param  number   $salt       加密盐值
     * @param  string   $dictionary a-z,A-Z,0-9 62个字符打乱后的字符串
     * @return void
     */
    public function __construct()
    {
        $conf               = config('sungmee.hashid');
        $this->conf         = $conf;
        $this->length       = $conf ? $conf['length'] : 8;
        $this->salt         = $conf ? $conf['salt'] : 3.14159265359;
        $this->dictionary   = $conf ? $conf['dictionary']
                : 'FH2h7v0VOL5NtZzaCnMwmUYsrykl81TiQEoDxI6feuAgdJGcj39BqRW4PpSKbX';
        $this->flag         = substr($this->dictionary, 0, $this->length);
        $this->substitute   = substr($this->dictionary, $this->length, 10);
        $this->alternate    = substr($this->dictionary, $this->length + 10);
    }

    /**
     * 加密 ID
     *
     * @date   2017/07/05 11:27:17
     * @param  integer $ids 需要加密的 ID 值
     * @return string
     */
    public function id2hash(int $ids)
    {
        $hash       = '';
        $ids_length = strlen($ids);
        $first      = substr($this->flag, $ids_length - 1, 1);

        // 密文的补缺位
        $keys_length = $this->length - $ids_length - 1;
        $keys        = str_replace('.', '', $ids / $this->salt);
        $keys        = substr($keys, -$keys_length);
        $keys        = str_split($keys);
        $alternates  = str_split($this->alternate);
        foreach($keys as $key)
        {
            $hash .= $alternates[$key];
        }

        $keys       = str_split($ids);
        $substitute = str_split($this->substitute);
        foreach($keys as $key)
        {
            $hash  .= $substitute[$key];
        }

        return $first . $hash;
    }

    /**
     * 解密 ID
     *
     * @date   2017/07/05 11:27:17
     * @param  string  $hash 需要解密的 Hash 值
     * @return integer
     */
    public function hash2id(string $hash)
    {
        $ids       = '';
        $first     = substr($hash, 0, 1);
        $length    = strpos($this->flag, $first);

        if($length !== false)
        {
            $length++;
            $search = str_split(substr($hash, -$length));
            foreach($search as $s)
            {
                $ids .= strpos($this->substitute, $s);
            }
        }

        return ctype_digit($ids) ? (int) $ids : null;
    }

    /**
     * 获取字典字符串
     *
     * @date   2017/07/05 11:27:17
     * @return string
     */
    public function dictionary()
    {
        $dictionary = str_split(str_random(518));
        $dictionary = array_unique($dictionary);
        return join('', $dictionary);
    }

    /**
     * 加密字符串，以便前后端互通密文
     *
     * @param   string $string
     * @return  string $hash
     */
    public function str2hash(string $string)
    {
        $string = urlencode($string);
        $key    = $this->conf['strHashKey'];
        $len    = strlen($key);
        $hash   = '';
        for($i = 0; $i < strlen ($string); $i++) {
            $k = $i % $len;
            $hash .= $string [$i] ^ $key [$k];
        }
        return urlencode(base64_encode($hash));
    }

    /**
     * 根据指定字符串，或随机字符串生成 UUID
     *
     * @param string    $chars  需要指定的字符串
     * @return string   $uuid   UUID
     */
    public function uuid(string $chars = null)
    {
        $chars = $chars ?: uniqid(mt_rand(), true);
        $chars = md5($chars);
        $uuid  = substr($chars,0,8) . '-';
        $uuid .= substr($chars,8,4) . '-';
        $uuid .= substr($chars,12,4) . '-';
        $uuid .= substr($chars,16,4) . '-';
        $uuid .= substr($chars,20,12);
        return $uuid;
    }
}