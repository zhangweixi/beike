%%%%%%%%%%%%%%%
% 判断GPS数据
% 2018-11-30
%%%%%%%%%%%%%%%
clc; close all;
%% 读数据
pathname = 'G:\1189';
gps_L = 'gps-L.txt'; court_config = 'court-config.txt';
addpath(genpath(pathname)); 
gps = importdata(gps_L); gps = GPS_pretreatment(gps);
court = importdata(court_config);
%% 画图
figure
plot(court(1:1000,1),court(1:1000,2),'.'); hold on % 划分的球场
for i = 1:1000 
    if court(i,3) == 1
        plot(court(i,1),court(i,2),'*'); hold on  % 射门区域
    end
    if court(i,4) == 1
        plot(court(i,1),court(i,2),'y*'); hold on  % 禁区
    end
end
% 球门
plot(court(1001,1),court(1001,2),'r*'); hold on 
plot(court(1001,3),court(1001,4),'r*'); hold on 
plot(court(1002,1),court(1002,2),'r*'); hold on 
plot(court(1002,3),court(1002,4),'r*'); hold on 
% 位置
plot(gps(:,1),gps(:,2),'bo'); axis equal


