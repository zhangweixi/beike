%%%%%%%%%%%%%%%%%%%%%%
% �ж�GPS����
% 2018-11-30
%%%%%%%%%%%%%%%%%%%%%%
clc; clear; close all;
%% ������
pathname = 'G:\275';
gps_L = 'gps-L.txt'; court_config = 'court-config.txt';
addpath(genpath(pathname)); 
gps = importdata(gps_L); 
% gps = GPS_pretreatment(gps);
court = importdata(court_config);
%% GPS ���ݴ���
GPS = GPS_pretreatment(gps);
% [Re_sample_lat,Re_sample_lon,Time] = GPS_inter(gps(:,1),gps(:,2),fs,10);
% figure;
% plot(GPS(:,1),GPS(:,2));
%% ��ͼ
figure
plot(court(1:1000,1),court(1:1000,2),'g.','markersize',20); hold on % ���ֵ���
for i = 1:1000 
    if court(i,3) == 1
        plot(court(i,1),court(i,2),'y.','markersize',20); hold on  % ��������
    end
    if court(i,4) == 1
        plot(court(i,1),court(i,2),'r.','markersize',20); hold on  % ����
    end
end
% ����
plot(court(1001,1),court(1001,2),'r<','markersize',12); hold on 
plot(court(1001,3),court(1001,4),'r<','markersize',12); hold on 
plot(court(1002,1),court(1002,2),'r>','markersize',12); hold on 
plot(court(1002,3),court(1002,4),'r>','markersize',12); hold on
% λ��
plot(GPS(:,1),GPS(:,2),'o','markersize',2); axis equal


