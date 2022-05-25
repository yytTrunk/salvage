<?php
declare (strict_types = 1);

namespace app\program\middleware;

use think\response\Json;

class checkAuth
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle($request, \Closure $next) :Json
    {
        //
        if (!$request->param('user_id')) {
            return \json([
                'code'       => 300,
                'message'    => '请授权登录',
            ]);
        }

        return $next($request);
    }
}
