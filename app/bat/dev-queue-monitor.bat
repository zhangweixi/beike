@echo off

rem 切换到脚本目录

%~d0%
cd  %~dp0%

rem 队列名称
set queueType=dev_matlab
rem 日志名称
set filename=queue-num.txt

setlocal enabledelayedexpansion

rem 列出当前所有的队列进程
wmic process where name="php.exe" | findstr %queueType% > %filename%

rem 统计进程数量
set /a proNum = 0
for /f %%i in (%filename%) do (
	set /a proNum+=1 
)

rem 获得队列执行命令的目录
cd ../../
set appdir=%cd%\artisan


rem 如果进程小于5，则开启一个新的进程
if %proNum% lss 5 (
	
	start /b php %appdir% queue:work --queue=%queueType%
	set /a proNum+=1
)

cd app/bat

echo process num:%proNum% >> %filename%
echo last update time:%date% %time% >> %filename%
