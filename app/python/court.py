#coding:utf-8

from pylab import *
from numpy import *
import matplotlib.pyplot as plt
import sys
import json
import math
import MySQLdb

import getopt

def get_argument(args,key):

    for opt_name,opt_value in args:

        if opt_name == key:
            return opt_value

    return False



#解析参数
args = sys.argv[1:]
args = getopt.getopt(args,'-h',['matchid=','init','savedata'])

sarg,larg = args
args = sarg + larg


#print(json.dumps(args,indent=4))



#合并列表
#程序分支入口

for opt_name,opt_value in sarg:

    #帮助
    if opt_name in ('-h','-help'):
        print('-h  get this module help')
        print('--init init this bool court')
        print('--savedata  coculate the end data')
        print('--matchid  point the match id')
        sys.exit()


    # 初始化球场
    elif opt_name in ('--init'):

        matchid = get_argument(args,'--matchid')

        if matchid == False:

            print('\nplease input argument --matchid\n')
            sys.exit()

        matchid = int(matchid)

        if matchid < 1:
            print('\nthis artument --matchid must > 1\n')
            sys.exit()

        print('reading to hand court ',matchid,"......")

    # 保存最终的数据
    elif opt_name == 'savedata':

        print('savedata')















sys.exit()
#解析足球数据
'''
已知4个点

'''


#一个经纬度坐标点
class Point():
    lat = 0
    lon = 0

    scale = 100000000

    def __init__(self, gps):

        gpsInfo = gps.split(',')

        self.lat = float(gpsInfo[0]) * self.scale
        self.lon = float(gpsInfo[1]) * self.scale



class Court():

    latNum = 10
    lonNum = 10

    points = []

    a = Point("0,0")
    d = Point("0,0")
    e = Point("0,0")
    f = Point("0,0")
    g = Point("0,0")
    h = Point("0,0")



    #切割足球场
    def cut_court(self):

        #先将矩形画成很多矩形
        left = []
        right= []
        middlePoints = []


        #a->d构成一条边  g-h构成一条边

        avg_a_d_lat = (self.d.lat - self.a.lat)/self.lonNum
        avg_a_d_lon = (self.d.lon - self.a.lon)/self.lonNum


        avg_g_h_lat = (self.h.lat - self.g.lat)/self.lonNum
        avg_g_h_lon = (self.h.lon - self.g.lon)/self.lonNum

        # 切分方格，并找到方格的中心点
        for i in range(0,self.lonNum+1):

            leftLat = self.a.lat + i * avg_a_d_lat
            leftLon = self.a.lon + i * avg_a_d_lon
            rightLat= self.g.lat + i * avg_g_h_lat
            rightLon= self.g.lon + i * avg_g_h_lon


            left.append({"lat":leftLat,"lon":leftLon})
            right.append({"lat":rightLat,"lon":rightLon})


            self.draw_point(leftLat,leftLon,'blue')
            self.draw_point(rightLat,rightLon,'blue')

            plt.scatter(leftLat,leftLon, c='red', s=50, alpha=0.4, marker='o')  # 散点图
            plt.scatter(rightLat, rightLon, c='red', s=50, alpha=0.4, marker='o')  # 散点图
            #plt.show()
            #sys.exit()


            # 这里得到左边点和右边点 把两个点链接组成一条线 再切分
            singleLat = (rightLat - leftLat)/self.latNum
            singleLon = (rightLon - rightLon)/self.latNum


            linePoints  = []

            if(i%2 == 1):

                for j in range(0,self.latNum+1):

                    lat = leftLat + j * singleLat
                    lon = leftLon + j * singleLon

                    if j % 2 == 1:

                        plt.scatter(lat, lon, c='green', s=50, alpha=0.4, marker='o')  # 散点图

                        linePoints.append({"lat":lat,"lon":lon})

                    else:

                        plt.scatter(lat, lon, c='red', s=50, alpha=0.4, marker='o')  # 散点图

                middlePoints.append(linePoints)

            #plt.show()
            #sys.exit()




        #将数据存储到数据库


        #print(json.dumps(right,indent=4))
        #print(json.dumps(middlePoints,indent=4))
        self.points = middlePoints



        #把数据存储在服务器


    def find_which_box(self,point:Point):

        dis = 1000000000000
        minDisBox = Point("0,0")

        for line in self.points:

            for box in line:


                newDis = (point.lat - box["lat"]) ** 2 + (point.lon - box["lon"]) ** 2
                print(newDis)

                if newDis < dis :

                    dis = newDis
                    minDisBox.lat = box['lat']
                    minDisBox.lon = box['lon']



        self.draw_point(minDisBox.lat,minDisBox.lon,'red')


    #绘制一个圆形
    def draw_point(self,lat,lon,c='red'):

        plt.scatter(lat, lon, c=c, s=50, alpha=0.4, marker='o')  # 散点图


    #创建一个完整的足球场 足球场呈现s型 |_|一|
    def create_full_court(self,a:Point,d:Point,e:Point,f:Point):

        self.a = a
        self.d = d
        self.e = e
        self.f = f

        lat1 = f.lat + (f.lat - a.lat)
        lon1 = f.lon + (f.lon - a.lon)

        self.g.lat = lat1
        self.g.lon = lon1


        lat2 = e.lat + (e.lat - d.lat)
        lon2 = e.lon + (e.lon - d.lon)

        self.h.lat = lat2
        self.h.lon = lon2

        self.draw_point(self.a.lat,self.a.lon)
        self.draw_point(self.d.lat,self.d.lon)
        self.draw_point(e.lat,e.lon,'black')
        self.draw_point(f.lat,f.lon)
        self.draw_point(self.g.lat,self.g.lon,'black')
        self.draw_point(self.h.lat,self.h.lon)

        #plt.show()
        #sys.exit()



#业务逻辑开始
a = Point("121.533785,31.320482")
b = Point("121.533812,31.317313")
e = Point("121.537095,31.317328")
f = Point("121.537118,31.320486")

p = Point("121.535995,31.320463")

a = Point("121.538492,31.330824")
b = Point("121.538564,31.327662")
e = Point("121.544942,31.327755")
f = Point("121.544924,31.330932")

court = Court()
court.create_full_court(a,b,e,f)
#court.cut_court()

#court.draw_point(p.lat,p.lon,'pink')

#court.find_which_box(p)

plt.show()


sys.exit()

xs = [a.lat,b.lat,e.lat,f.lat,a.lat]
ys = [a.lon,b.lon,e.lon,f.lon,a.lon]



plt.plot(xs,ys)



plt.scatter(a.lat,a.lon,c='red',s=50,alpha=0.4,marker='o')#散点图


plt.show()

sys.exit()


x = linspace(0, 5, 10)
y = x ** 2

fig = plt.figure()

axes = fig.add_axes([0.1, 0.1, 0.8, 0.8]) # left, bottom, width, height (range 0 to 1)

axes.plot(x, y, 'r')

axes.set_xlabel('x')
axes.set_ylabel('y')
axes.set_title('title')

plt.show()