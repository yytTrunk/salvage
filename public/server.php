<?php

//创建socket套接字
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//设置阻塞模式
socket_set_block($socket);
//为套接字绑定ip和端口
socket_bind($socket,'127.0.0.1',999);
//监听socket
socket_listen($socket,4);

while(true)
{
    //接收客户端请求
    if(($msgsocket = socket_accept($socket)) !== false)
    {
        //读取请求内容
        $buf = socket_read($msgsocket, 8192);
        echo "Received msg: $buf \n";
        $str = "this is a service message";
        //向连接的客户端发送数据
        socket_write($msgsocket, $buf,strlen($buf));
        //操作完之后需要关闭该连接否则 feof() 函数无法正确识别打开的句柄是否读取完成
        socket_close($msgsocket);
    }
}
