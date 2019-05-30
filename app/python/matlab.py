#coding:utf-8

#建立一个服务器
import getopt
import sys
import os
import matlabserver as server






options,args = getopt.getopt(sys.argv[1:],"k:i:")

coms = ['start','stop']
k = ""
for i in options:
    if i[0] == "-k":
        k = i[1]
        break

if k == '':
    sys.exit('you should input commands like: main.py -k start:stop')




matserver = server.server()

if k == "start":
    matserver.start_server()
    #os.system('cls')
elif k =='stop':

    matserver.stop_server()


elif k =='restart':
    print('stoping service ...')
    matserver.stop_server()
    print('restart service ...')
    matserver.start_server()

else:

    sys.exit('error')