ThinkPHP 6.0
===============

> 运行环境要求PHP7.1+，兼容PHP8.0。

[官方应用服务市场](https://market.topthink.com) | [`ThinkAPI`——官方统一API服务](https://docs.topthink.com/think-api)

ThinkPHPV6.0。

## 文档

[完全开发手册](https://www.kancloud.cn/manual/thinkphp6_0/content)
http://doc3.workerman.net/  

## 参与开发

请参阅 [ThinkPHP 核心框架包](https://github.com/top-think/framework)。


## 启动
thinkphp6.0框架
采用workman监听服务
监听命令 999端口998端口
999端口监听硬件
998端口监听gps
990端口监听gds
cd 到项目public
php think worker:server -d 守护进程启动
php think worker:server start 普通启动
php think worker:server status 查看状态
php think worker:server stop 停止监听

本地需要，远程依赖于宝塔不需要执行下面命令来启动服务
php think stop 关闭守护进程
php think run  本地打开web服务

第二种关闭服务
lsof -i:999
kill -9 进程


### 日志
创建目录下 /runtime/myworkman/log  记录业务workman日志
设置cache目录为root权限，避免不能写入


### 测试报文
460000000000A0154c000000930000009300000093000000930000000100000000000000010101010101010000323531372e323833314e3131372e323432333134450000ffff

## gps 设备
### gps 测试数据
$65,S,1033020,N,3020.4517,E,12005.4156,0.000,35.60,VER2,30,0.60,250122-062802,18,2,8,387,63,010,025,102,#

### 上报
带着设备动起来是15秒，放在外面不动的话最慢2分钟一条
可以持续工作30多小时

## hclm 设备
### hclm 测试数据
46000000020000004c000000930000009300000093000000930000000100000000000000010101010101010000323531372e323833314e3131372e323432333134450000ffff
02表示设备编号
