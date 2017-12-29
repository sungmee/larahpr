<?php

namespace Sungmee\Larahpr\Helpers;

use Sungmee\Larahpr\HPR;
use Illuminate\Http\Request;

class Api extends HPR
{
    /**
     * 默认状态码
     *
     * @var int
     */
    protected $statusCode = 200;

    /**
     * 错误消息
     *
     * @var int
     *
    * 状态说明
    *
    * 200 GET 请求成功，及 DELETE 或 PATCH 同步请求完成，或者 PUT 同步更新一个已存在的资源
    * 201 POST 同步请求完成，或者 PUT 同步创建一个新的资源
    * 202 POST，PUT，DELETE，或 PATCH 请求接收，将被异步处理
    * 204 无内容。当一个动作执行成功，但没有内容返回。
    * 206 GET 请求成功，但是只返回一部分，参考：上文中范围分页

    * 400 Bad request. 错误的请求。无法通过验证的请求的标准选项。
    * 401 Unauthorized. 未经授权。用户需要进行身份验证。
    * 403 Forbidden. 无权限。用户已通过身份验证，但没有执行操作的权限。
    * 404 Not found. 未找到。没有找到相关资源。
    * 422 Unprocessable Entity. 请求被服务器正确解析，但是包含无效字段
    * 429 Too Many Requests. 因为访问频繁，你已经被限制访问，稍后重试

    * 500 Internal Server Error: 服务器错误。
    * 503 Service unavailable. 暂停服务。
     */
    protected $messages = [
        200 => 'Success.',
        204 => 'Ok',
        400 => 'Bad Request.',              // 错误的请求。无法通过验证的请求的标准选项。
        401 => 'Unauthorized.',             // 未经授权。用户需要进行身份验证。
        403 => 'Forbidden.',                // 无权限。用户已通过身份验证，但没有执行操作的权限。
        404 => 'Not Found.',                // 未找到。没有找到相关资源。
        422 => 'Unprocessable Entity.',     // 请求被服务器正确解析，但是包含无效字段。
        429 => 'Too Many Requests.',        // 因为访问频繁，你已经被限制访问，稍后重试。
        500 => 'Internal Server Error.',    // 服务器错误。
        503 => 'Service unavailable.'       // 暂停服务。
    ];

	/**
     * 创建一个新实例。
     *
     * @return void
     */
    public function __construct()
    {
		//
    }

    public function __call($name, $args)
    {
        $field = preg_match('/^response(\d+)/', $name, $matches);
        if ($field && $matches[1]) {
            @$message = $args[0] ?: __($this->messages[$matches[1]]);
            $statusCode = $matches[1];
            return $this->error($message, $statusCode);
        }

        parent::__call($name, $args);
    }

    /**
     * 基本的响应方法
     *
     * @param  array $data
     * @return json  $response
     */
    public function response($data)
    {
        return response()->json($data, $this->statusCode);
    }

    /**
     * 程序抛出的错误。
     *
     * @param  string $message
     * @return json   $this->response
     */
    public function exception($e)
    {
        $statusCode = $e->getCode();
        $message = $e->getMessage();

        $message = $statusCode < 100
            ? "[$statusCode] $message"
            : $message ?: __('Service unavailable.');

        $statusCode = $statusCode < 100 ? 500 : $statusCode;

        return $this->error($message, $statusCode);
    }

    /**
     * 错误的请求。无法通过验证的请求的标准选项。
     *
     * @param  string $message
     * @return json   $this->response
     */
    public function error($message = null, $statusCode = 400, $errors = [])
    {
        $message = $message ?: __('Bad Request.');

        $this->statusCode = $statusCode;

        return $this->response([
            'errors'  => $errors,
            'message' => $message
        ]);
    }

    /**
     * 请求数据的成功响应
     *
     * @param  string $message [description]
     * @return json   $this->response
     */
    public function success($data, $message = null)
    {
        $message = $message ?: __('Success.');

        return $this->response([
            'data' => $data
        ]);
    }

    /**
     * 不带数据的状态成功响应
     *
     * @return json   $this->response
     */
    public function ok()
    {
        $this->statusCode = 204;

        return $this->response([
            'message' => 'Ok'
        ]);
    }

    /**
     * 分页请求数据的成功响应
     *
     * @param  string $message [description]
     * @return json   $this->response
     */
    public function responsePaginate(Request $request, $data, $message = null)
    {
        $message = $message ?: __('Success.');
        $data = is_array($data) ? collect($data) : $data;
        $total = $data->count();
        $per_page = $request->per_page && is_numeric($request->per_page) ? (int) $request->per_page : 15;
        $current_page = $request->page && is_numeric($request->page) ? (int) $request->page : 1;
        $last_page = ceil($total / $per_page);

        return $this->response(['data' => [
            'total' => $total,
            'per_page' => $per_page,
            'current_page' => $current_page,
            'last_page' => $last_page,
            'from' => $per_page * ($current_page - 1) + 1,
            'to' => ($current_page == $last_page) ? $total : ($per_page * $current_page),
            'data' => array_values($data->forPage($current_page, $per_page)->all())
        ]]);
    }

    /**
     * 请求数据的成功响应
     *
     * @param  collection||array $data           需要返回给客户端的原始数据
     * @param  object            $transformer    数据加工变形器
     * @param  string            $message        客户端的通知信息
     * @return json              $this->response 返回客户端 JSON 数据
     */
    public function responseItem($data, $transformer, $message = null)
    {
        if (gettype($transformer) === 'object') {
            $data = $transformer->transform($data);
            return $this->responseSuccess($data);
        }
    }

    /**
     * 请求数据的成功响应
     *
     * @param  collection||array $data           需要返回给客户端的原始数据
     * @param  object            $transformer    数据加工变形器
     * @param  string            $message        客户端的通知信息
     * @return json              $this->response 返回客户端 JSON 数据
     */
    public function responseItems($data, $transformer, $message = null)
    {
        if (gettype($transformer) === 'object') {
            $data = $transformer->transforms($data);
            return $this->responseSuccess($data);
        }
    }

    /**
     * Api 表单验证器，未通过验证时，通过 JSON 返回错误信息
     *
     * @param  $request Illuminate\Http\Request
     * @param  array    $rules
     * @return json     $this->response 返回客户端 JSON 数据
     */
    public function apiValidate(Request $request, array $rules)
    {
        $validator = \Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return $this->responseError('表单验证错误', 422, $validator->errors());
        }
    }
}
