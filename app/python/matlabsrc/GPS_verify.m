%%%%%%%%%%%%%%%
% �ж�GPS����
% 2018-11-30
%%%%%%%%%%%%%%%
clc; close all;
%% ������
pathname = 'G:\1189';
gps_L = 'gps-L.txt'; court_config = 'court-config.txt';
addpath(genpath(pathname)); 
gps = importdata(gps_L); gps = GPS_pretreatment(gps);
court = importdata(court_config);
%% ��ͼ
figure
plot(court(1:1000,1),court(1:1000,2),'.'); hold on % ���ֵ���
for i = 1:1000 
    if court(i,3) == 1
        plot(court(i,1),court(i,2),'*'); hold on  % ��������
    end
    if court(i,4) == 1
        plot(court(i,1),court(i,2),'y*'); hold on  % ����
    end
end
% ����
plot(court(1001,1),court(1001,2),'r*'); hold on 
plot(court(1001,3),court(1001,4),'r*'); hold on 
plot(court(1002,1),court(1002,2),'r*'); hold on 
plot(court(1002,3),court(1002,4),'r*'); hold on 
% λ��
plot(gps(:,1),gps(:,2),'bo'); axis equal


