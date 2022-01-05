<?php
/*
|----------------------------------------------------------------------------
| TopWindow [ Internet Ecological traffic aggregation and sharing platform ]
|----------------------------------------------------------------------------
| Copyright (c) 2006-2019 http://yangrong1.cn All rights reserved.
|----------------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
|----------------------------------------------------------------------------
| Author: yangrong <yangrong2@gmail.com>
|----------------------------------------------------------------------------
*/
declare (strict_types=1);
namespace Learn\Input;

use voku\helper\AntiXSS;
use voku\helper\UTF8;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
class Input
{
    /**
     * 请求实例
     *
     * @var \Illuminate\Http\Request
     */
    protected $request;
    /**
     * 反XSS实例
     *
     * @var \voku\helper\AntiXSS
     */
    private $antiXss;
    /**
     * Object Oriented
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array|null  $evil
     * @param  string|null $replace
     * @return void
     */
    public function __construct(Request $request, array $evil = null, string $replace = null)
    {
        $this->request = $request;
        $this->createAntiXSS($evil, $replace);
    }
    /**
     * 创建一个AntiXSS实例
     *
     * @param  array|null  $evil
     * @param  string|null  $replace
     * @return void
     */
    private function createAntiXSS(array $evil = null, string $replace = null)
    {
        $antiXss = new AntiXSS();
        if ($replace !== null) {
            $antiXss->setReplacement($replace);
        }
        if ($evil !== null) {
            self::addEvilOptions($antiXss, $evil);
        }
        $this->antiXss = $antiXss;
    }
    /**
     * 获取请求的所有请求数据和文件
     *
     * @param  bool  $trim
     * @param  bool  $clean
     * @return array
     */
    public function all(bool $trim = true, bool $clean = true)
    {
        $values = $this->request->all();
        return $this->clean($values, $trim, $clean);
    }
    /**
     * 从请求中获取请求项
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @param  bool  $trim
     * @param  bool  $clean
     * @return mixed
     */
    public function get(string $key = null, $default = null, bool $trim = true, bool $clean = true)
    {
        $value = $this->request->input($key, $default);
        return $this->clean($value, $trim, $clean);
    }
    /**
     * 从请求中获取请求项（这是get方法的别名）
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @param  bool  $trim
     * @param  bool  $clean
     * @return mixed
     */
    public function input(string $key = null, $default = null, bool $trim = true, bool $clean = true)
    {
        return $this->get($key, $default, $trim, $clean);
    }
    /**
     * 以布尔值形式检索输入（当值为“1”、“true”、“on”和“yes”时返回true。否则，返回false）
     *
     * @param  string|null  $key
     * @param  bool  $default
     * @param  bool  $trim
     * @param  bool  $clean
     * @return bool
     */
    public function bool(string $key = null, bool $default = false, bool $trim = true, bool $clean = true)
    {
        return filter_var($this->get($key, $default, $trim, $clean), \FILTER_VALIDATE_BOOLEAN);
    }
    /**
     * 从请求数据中获取项目的子集
     *
     * @param  string|string[]  $keys
     * @param  bool  $trim
     * @param  bool  $clean
     * @return array
     */
    public function only($keys, bool $trim = true, bool $clean = true)
    {
        $values = [];
        foreach ((array) $keys as $key) {
            $values[$key] = $this->get($key, null, $trim, $clean);
        }
        return $values;
    }
    /**
     * 获取除指定项数组之外的所有请求
     *
     * @param  string|string[]  $keys
     * @param  bool  $trim
     * @param  bool  $clean
     * @return array
     */
    public function except($keys, bool $trim = true, bool $clean = true)
    {
        $values = $this->request->except((array) $keys);
        return $this->clean($values, $trim, $clean);
    }
    /**
     * 从请求数据中获取项目的映射子集
     *
     * @param  string[]  $keys
     * @param  bool  $trim
     * @param  bool  $clean
     * @return array
     */
    public function map(array $keys, bool $trim = true, bool $clean = true)
    {
        $values = $this->only(array_keys($keys), $trim, $clean);
        $new = [];
        foreach ($keys as $key => $value) {
            $new[$value] = Arr::get($values, $key);
        }
        return $new;
    }
    /**
     * 从请求中获取旧的输入项
     *
     * @param  string|null  $key
     * @param  mixed  $default
     * @param  bool  $trim
     * @param  bool  $clean
     * @return mixed
     */
    public function old(string $key = null, $default = null, bool $trim = true, bool $clean = true)
    {
        $value = $this->request->old($key, $default);
        return $this->clean($value, $trim, $clean);
    }
    /**
     * 过滤指定的一个或多个值
     *
     * @param  mixed  $value
     * @param  bool  $trim
     * @param  bool  $clean
     * @return mixed
     */
    public function clean($value, bool $trim = true, bool $clean = true)
    {
        if ($value === null || is_bool($value) || is_int($value) || is_float($value)) {
            return $value;
        }
        if (!is_array($value)) {
            return $this->process((string) $value, $trim, $clean);
        }
        $final = [];
        foreach ($value as $k => $v) {
            if ($v !== null) {
                $final[$k] = $this->clean($v, $trim, $clean);
            }
        }
        return $final;
    }
    /**
     * XSS过滤
     *
     * @param  string|array  $input
     * @return string|array
     */
    public function xssClean($input)
    {
        $output = $this->antiXss->xss_clean($input);
        // 移除不可见的字符
        if ($this->antiXss->isXssFound() === false) {
            return self::cleanInvisibleCharacters($output);
        }
        return $output;
    }
    /**
     * 处理指定的值
     *
     * @param  string  $value
     * @param  bool  $trim
     * @param  bool  $clean
     * @return string
     */
    protected function process(string $value, bool $trim, bool $clean)
    {
        if ($trim) {
            $value = trim($value);
        }
        if ($clean) {
            $value = $this->xssClean($value);
        }
        if ($trim) {
            $value = trim($value);
        }
        return $value;
    }
    /**
     * @return \Illuminate\Http\Request
     */
    public function getRequest()
    {
        return $this->request;
    }
    /**
     * @param  \Illuminate\Http\Request  $request
     * @return void
     */
    public function withRequest(Request $request)
    {
        $this->request = $request;
    }
    /**
     * 添加给定的过滤选项
     *
     * @param  \voku\helper\AntiXSS  $antiXss
     * @param  array  $evil
     * @return void
     */
    private static function addEvilOptions(AntiXSS $antiXss, array $evil)
    {
        if (isset($evil['attributes']) || isset($evil['tags'])) {
            $antiXss->addEvilAttributes($evil['attributes'] ?? []);
            $antiXss->addEvilHtmlTags($evil['tags'] ?? []);
        } else {
            $antiXss->addEvilAttributes($evil);
        }
    }
    /**
     * 清除不可见字符
     *
     * @param  string|array  $input
     * @return string|array
     */
    private static function cleanInvisibleCharacters($input)
    {
        if (is_array($input)) {
            foreach ($input as $key => &$value) {
                $value = self::cleanInvisibleCharacters($value);
            }
            return $input;
        }
        return UTF8::remove_invisible_characters($input, true);
    }
    /**
     * 动态调用
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        return call_user_func_array([$this->request, $method], $parameters);
    }
}