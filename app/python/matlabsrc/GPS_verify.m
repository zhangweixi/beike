%%%%%%%%%%%%%%%%%%%%%%
% �ж�GPS����
% 2018-11-30
%%%%%%%%%%%%%%%%%%%%%%
clc; close all;
%% ������
pathname = 'G:\1257';
gps_L = 'gps-L.txt'; court_config = 'court-config.txt';
addpath(genpath(pathname)); 
gps = importdata(gps_L); gps = GPS_pretreatment(gps);
court = importdata(court_config);
%% ��ͼ
figure
plot(court(1:1000,1),court(1:1000,2),'g.'); hold on % ���ֵ���
for i = 1:1000 
    if court(i,3) == 1
        plot(court(i,1),court(i,2),'y.'); hold on  % ��������
    end
    if court(i,4) == 1
        plot(court(i,1),court(i,2),'r.'); hold on  % ����
    end
end
% ����
plot(court(1001,1),court(1001,2),'b<','markersize',8); hold on 
plot(court(1001,3),court(1001,4),'b<','markersize',8); hold on 
plot(court(1002,1),court(1002,2),'b>','markersize',8); hold on 
plot(court(1002,3),court(1002,4),'b>','markersize',8); hold on
% λ��
plot(gps(:,1),gps(:,2),'o','markersize',2); axis equal


