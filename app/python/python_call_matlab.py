#coding:utf-8
import sys
import time
import json
import os
from win32com.client import Dispatch
import getopt


#显示帮助信息
def show_help():
	print("python python_call_matlab.py --sensorl=x --sensorr=x --compassl=x --compassr=x --gps=x --config=x --callbackurl=xx --result=xx")


params = ["sensorl=","sensorr=","compassl=","compassr=","gps=","config=","callbackurl=","result="]
options,args = getopt.getopt(sys.argv[1:],"h",params)


files = {"sensorl":"","sensorr":"","compassl":"","compassr":"","gps":"","config":"","callbackurl":"","result":""}


for i in options:

	argname = i[0]
	argvalue = i[1]
	argname = argname.strip("-")

	if argname == "h":

		print('\nplease input command like this:\n')
		show_help()

		sys.exit()

	files[argname] = argvalue


#检查参数是否齐全
for f in files:

	if files[f] == "" and f != "config":

		print("\nneed file '" + f + "'\n")
		show_help()
		sys.exit()


if True:

	command = "fun('{sensorl}','{sensorr}','{compassl}','{compassr}','{gps}','{config}','{callbackurl}','{result}')".format_map(files)

	print(command)
	sys.exit()


	#3.调用函数
	#command = "beike('{sourcefile}','{resultfile}')".format_map(vars())
	command = "matlabfunctionadd(1,10)"

	matlabApp = Dispatch('Matlab.application')

	#1.切换工作目录
	workplacedir = os.getcwd()
	matlabApp.execute("cd " + workplacedir)
	
	#2.添加matlab脚本所在的目录到路径中
	matlabApp.execute("addpath('"+ workplacedir +"')")

	result = matlabApp.execute(command)


	print(result)


