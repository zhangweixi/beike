%%%%%%%%%%%%%%%%%%%%%%
% 判断GPS数据
% 2018-11-30
%%%%%%%%%%%%%%%%%%%%%%
clc; clear; close all;
%% 读数据
pathname = 'G:\287';
sensor_R = 'sensor-R.txt'; sensor_L = 'sensor-L.txt'; gps_L = 'gps-L.txt';
angle_R = 'angle-R.txt'; angle_L = 'angle-L.txt'; court_config = 'court-config.txt';
addpath(genpath(pathname)); 
sensor_r = importdata(sensor_R)/1000; sensor_l = importdata(sensor_L)/1000; 
sensor_r(:,4:5) = sensor_r(:,4:5)*1000; sensor_l(:,4:5) = sensor_l(:,4:5)*1000;
Compass_R = importdata(angle_R); Compass_L = importdata(angle_L); 
gps = importdata(gps_L);
court = importdata(court_config);
%% GPS 数据处理
GPS = GPS_pretreatment(gps);
% [Re_sample_lat,Re_sample_lon,Time] = GPS_inter(gps(:,1),gps(:,2),fs,10);
% figure;
% plot(GPS(:,1),GPS(:,2));
%% 画图
figure; axis equal; hold on
plot(court(1:1000,1),court(1:1000,2),'g.','markersize',20); % 划分的球场
for i = 1:1000 
    if court(i,3) == 1
        plot(court(i,1),court(i,2),'y.','markersize',20); % 射门区域
    end
    if court(i,4) == 1
        plot(court(i,1),court(i,2),'r.','markersize',20);  % 禁区
    end
end
%%
pass = Total_ball(sensor_r,sensor_l,gps);
shoot_result = Shoot_Z(pass,Compass_R,Compass_L,40,court);
[m,~] = size(pass); chu = 0; chang = 0; duan = 0;
for j = 1:m
    if pass(j,2) == 1
%         plot(pass(j,5),pass(j,6),'rh','markersize',20); hold on % 长传  
        plot(pass(j,3),pass(j,4),'rh','markersize',20); hold on % 长传  
        chang = chang+1;
    end
    if pass(j,2) == 2
%         plot(pass(j,5),pass(j,6),'kp','markersize',20); hold on % 短传
        plot(pass(j,3),pass(j,4),'kp','markersize',20); hold on % 长传  
        duan = duan+1;
    end
    if pass(j,2) == 3
%         plot(pass(j,5),pass(j,6),'b.','markersize',20); hold on % 触球
        plot(pass(j,3),pass(j,4),'b.','markersize',20); hold on % 长传  
        chu = chu+1;
    end    
end
if ~isempty(shoot_result)
    plot(shoot_result(:,1),shoot_result(:,2),'rh','markersize',15); hold on % 射门
end
% 球门
plot(court(1001,1),court(1001,2),'r<','markersize',12); hold on 
plot(court(1001,3),court(1001,4),'r<','markersize',12); hold on 
plot(court(1002,1),court(1002,2),'r>','markersize',12); hold on 
plot(court(1002,3),court(1002,4),'r>','markersize',12); hold on
%% 位置
% plot(GPS(:,1),GPS(:,2),'o','markersize',2); axis equal


