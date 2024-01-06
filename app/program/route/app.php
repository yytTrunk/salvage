<?php

use think\facade\Route;

Route::get('/','index/index');
Route::any('/sms','index/sms'); //验证码
Route::post('/login','index/login');
Route::post('/defaultLogin','index/defaultLogin'); //首页默认登录
Route::post('/register','index/register');
Route::post('/sendMessage','index/send');
Route::post('/upload','index/upload');
Route::post('/admin/role30/lock','index/lockRole30');
Route::post('/admin/role30/unlock','index/unLockRole30');
Route::post('/admin/role20/lock','index/lockRole20');
Route::post('/admin/role20/unlock','index/unLockRole20');
Route::post('/admin/role10/lock','index/lockRole10');
Route::post('/admin/role10/unlock','index/unLockRole10');
Route::post('/admin','index/admin');
Route::post('/facility','index/queryAllFacility');
Route::put('/facility','index/updateFacility');


Route::post('/yacht/add','manage/add');
Route::post('/yacht/delete','manage/delete');
Route::post('/yacht/lists','manage/lists');
Route::post('/yacht/lock','manage/lockYacht');
Route::post('/yacht/unlock','manage/unLockYacht');
Route::any('/yacht/modifyYacht','manage/modifyYacht');



Route::any('/getCode','manage/getCode');


Route::post('/duty/alarmLists','duty/alarmLists');
Route::post('/duty/alarmLogLists','duty/alarmLogLists');
Route::post('/duty/acceptAlarm','duty/acceptAlarm');
Route::post('/duty/cancelAlarm','duty/cancelAlarm');
Route::post('/duty/completeAlarm','duty/completeAlarm');
Route::post('/duty/conveyAlarm','duty/conveyAlarm');
Route::post('/duty/alarmDetail','duty/alarmDetail');
Route::post('/duty/handleYSMsg','duty/handleYSMsg');


Route::post('/duty/facilityGps','duty/facilityGps');
Route::post('/duty/facilityGpsLog','duty/facilityGpsLog');



Route::post('/hclm/login','hclm/login');
Route::post('/hclm/register','hclm/register');
Route::get('/hclm/sms','hclm/sms');
Route::post('/hclm/alarmPage','hclm/alarmPage');
Route::post('/hclm/handleAlarm','hclm/handleAlarm');
Route::post('/hclm/alarmDetail','hclm/alarmDetail');


