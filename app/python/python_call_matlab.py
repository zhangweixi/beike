#coding:utf-8
import sys
import time
import json
import os
from win32com.client import Dispatch
import getopt


	
options,args = getopt.getopt(sys.argv[1:],"h",['command='])

params = {}


for i in options:
	
	argname 	= i[0].strip("-")

	if argname == "h":
	
		print("python python_call_matlab.py \n -h help \n --command=x")
		sys.eixt()

	params[argname] = i[1]


#计算比赛数据的命令格式
#command = "LanQi('dir','sensorl','sensorr','compassl','compassr','gps','config','callbackurl','result')"


#计算足球场的命令格式
#command = ""






command = params['command']

#1.切换工作目录
#workplacedir = os.getcwd()+"/matlabsrc"
workplacedir = os.path.dirname(__file__) + "/matlabsrc"


matlabApp = Dispatch('Matlab.application')
matlabApp.execute("cd " + workplacedir)
matlabApp.execute("addpath('"+ workplacedir +"')")#2.添加matlab脚本所在的目录到路径中
result = matlabApp.execute(command)
print(result)


