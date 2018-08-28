import os
import time

#要保持的进程数量
pid = os.getpid()

totalpsnum = 1
php = "E:\phpstudy\PHPTutorial\php\php-7.2.5-nts\php.exe"

command = "start /b " + php + " E:\phpstudy\PHPTutorial\WWW\launchever\\api.launchever.cn\\artisan queue:listen"

print(command)

while True:

	res = os.system('tasklist | findstr php.exe > python-queue.pid')

	f = open('python-queue.pid','r+',encoding='utf-8')

	
	line = f.readline()

	pslist=[]

	psnum = 0
	
	while line:
		
		pslist.append(line)

		line = f.readline()

		psnum = psnum + 1


	newpsnum = totalpsnum - int(psnum/2)
	
	print("open new")
	print(newpsnum)


	if newpsnum > 0:

		for i in range(newpsnum):
			

			os.system(command)
		
	psnum = 0

	f.write(str(pid))

	f.close()

	time.sleep(5)

