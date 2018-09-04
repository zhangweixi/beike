#coding:utf-8
import os
import socket
from win32com.client import Dispatch
import random
import platform
import signal
import sys
import time

class server():

    pidfile = ""
    port = 9999


    def __init__(self):
        self.pidfile = os.path.dirname(__file__)+"/pidfile.pid"

    #开启服务
    def start_server(self):
        print('read to start models...')

        #写入pid
        pid = os.getpid()
        f = open(self.pidfile,'w')
        f.write(str(pid))
        f.close()

        #启动matlab
        matlab = Dispatch('Matlab.application')

        #创建socket
        serversocket = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        #host = socket.gethostname()
        host = "0.0.0.0"

        serversocket.bind((host,self.port))
        serversocket.listen(4)

        print('service started')
        while True:
            clientsocket, addr = serversocket.accept()

            clientsocket.close()


    #停止服务
    def stop_server(self):

        osname = platform.system().lower()

        f = open(self.pidfile, 'r')
        pid = f.read()

        try:

            if osname == 'windows':
                print('killing ps')
                os.popen('taskkill.exe /pid:' + pid + " -f")
                print("服务已停止")

            else:

                a = os.kill(pid, signal.SIGKILL)
                print("服务已停止")

            matlab = Dispatch('Matlab.application')
            matlab.execute("quit")

        except OSError:

            print(OSError)
            print('服务未启动')


    #重启服务
    def restart(self):
        self.stop_server()
        self.stop_server()


    #解析数据
    def get_data(self,result):

        data    = {}
        result  = result.split("&")

        for i in result:
            result1 = i.split("=")
            data[result1[0]] = result1[1]

        return data

