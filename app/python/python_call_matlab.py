#coding:utf-8
import sys
import time
import json
import os
from win32com.client import Dispatch

matlabApp = Dispatch('Matlab.application')

#1.切换工作目录
workplacedir = os.getcwd()
self.matlabApp.execute("cd " + workplacedir)

#2.添加matlab脚本所在的目录到路径中

matlabApp.execute("addpath('"+ workplacedir +"')")


#3.调用函数
command = "beike('{sourcefile}','{resultfile}')".format_map(vars())
result = matlabApp.execute(command)


print(result)