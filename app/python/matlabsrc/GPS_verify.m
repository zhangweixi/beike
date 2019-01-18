%%%%%%%%%%%%%%%%%%%%%%
% 判断GPS数据
% 2018-11-30
%%%%%%%%%%%%%%%%%%%%%%
clc; clear; close all;
%% 读数据
pathname = 'G:\275';
gps_L = 'gps-L.txt'; court_config = 'court-config.txt';
addpath(genpath(pathname)); 
gps = importdata(gps_L); 
% gps = GPS_pretreatment(gps);
court = importdata(court_config);
%% GPS 数据处理
GPS = GPS_pretreatment(gps);
% [Re_sample_lat,Re_sample_lon,Time] = GPS_inter(gps(:,1),gps(:,2),fs,10);
% figure;
% plot(GPS(:,1),GPS(:,2));
%% 画图
figure
plot(court(1:1000,1),court(1:1000,2),'g.','markersize',20); hold on % 划分的球场
for i = 1:1000 
    if court(i,3) == 1
        plot(court(i,1),court(i,2),'y.','markersize',20); hold on  % 射门区域
    end
    if court(i,4) == 1
        plot(court(i,1),court(i,2),'r.','markersize',20); hold on  % 禁区
    end
end
% 球门
plot(court(1001,1),court(1001,2),'r<','markersize',12); hold on 
plot(court(1001,3),court(1001,4),'r<','markersize',12); hold on 
plot(court(1002,1),court(1002,2),'r>','markersize',12); hold on 
plot(court(1002,3),court(1002,4),'r>','markersize',12); hold on
% 位置
plot(GPS(:,1),GPS(:,2),'o','markersize',2); axis equal


