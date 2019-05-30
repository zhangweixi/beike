#coding:utf-8

from pylab import *
from numpy import *
import matplotlib.pyplot as plt
import sys
import json
import math
import MySQLdb

import urllib


def draw(lat,lon,c="red"):
 	plt.scatter(lat, lon, c=c, s=50, alpha=0.4, marker='o')  # 散点图


#获得json数据
url = "http://dev1.api.launchever.cn/api/v1/court/test"
request = urllib.request.Request(url=url,method="POST")#.read(1000)
result = urllib.request.urlopen(request)
content = result.read()
content = bytes.decode(content)
data = json.loads(content)

A_D = data['A_D']
AF_DE = data["AF_DE"]

for p in A_D:
	print(p)
	draw(p['lat'],p['lon'],'green')

for p in AF_DE:
	#pass
	draw(p['lat'],p['lon'])




#for points in data:
	
#	for p in points:

	
#		draw(p['lat'],p['lon'])



plt.title('title')
plt.xlabel('lat')
plt.ylabel('lon')
plt.xlim([0,100])

#plt.axhspan(ymin=0,ymax=10,xmin=0,xmax=10)
#plt.angle_spectrum()

ax = plt.gca() 
ax.xaxis.set_ticks_position('bottom')
ax.yaxis.set_ticks_position('left')

ax.invert_yaxis()

plt.show()





#print(data)