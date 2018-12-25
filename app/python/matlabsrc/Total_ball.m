%%%%%%%%%%%%%%%%%%%%%%%%%%
% ���ҽŴ������ݶԱ�
% 2018-11-20
%%%%%%%%%%%%%%%%%%%%%%%%%%
% clc; clear; close all;
% pathname = 'G:\134';
% sensor_R = 'sensor-R.txt'; sensor_L = 'sensor-L.txt'; gps_L = 'gps-L.txt';
% % ���·��
% addpath(genpath(pathname)); 
% % Sensor
% Sensor_R = importdata(sensor_R)/1000; Sensor_L = importdata(sensor_L)/1000; 
% Sensor_R(:,4:5) = Sensor_R(:,4:5)*1000; Sensor_L(:,4:5) = Sensor_L(:,4:5)*1000;
% gps = importdata(gps_L);
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function PASS = Total_ball(Sensor_R,Sensor_L,gps)
% ���ҽŵĴ�������
% pass = BALL(Sensor_R,Sensor_L,filterlat,filterlon,sensor_fs); BALL_Z(sensor_r,sensor_l,gps)
pass = BALL_Z(Sensor_R,Sensor_L,gps);
% �ж���û������
if isempty(pass) 
    PASS = [];
    return;
end
% ����ʱ��������
Total = sortrows(pass,[3 4]); % PASS = Total ;
[m,~] = size(Total); j = 1; PASS = []; PASS(j,:) = Total(1,:);
for i = 2:m
    switch  PASS(j,2)
        case 3 % �����ж�
            if Total(i,3) - PASS(j,3) < 3000
                [~,z] = max([Total(i,7),PASS(j,7)]);
                if z == 1
                    PASS(j,:) = Total(i,:);
                end
            else
                j = j+1;
                PASS(j,:) = Total(i,:);
            end
        case 2   % �̴��ж�
            if Total(i,3) - PASS(j,3) < 6000
                [~,z] = max([Total(i,7),PASS(j,7)]);
                if z == 1
                    PASS(j,:) = Total(i,:);
                end
           else
                j = j+1;
                PASS(j,:) = Total(i,:);
            end
        case 1    % �����ж�
            if Total(i,3) - PASS(j,3) < 15000
                [~,z] = max([Total(i,7),PASS(j,7)]);
                if z == 1
                    PASS(j,:) = Total(i,:);
                end
            else
                j = j+1;
                PASS(j,:) = Total(i,:);
            end 
    end
end
% �жϴ������
flag = zeros(1,3);
for i = 1:length(PASS)
    if PASS(i,2) == 3
        flag(1,1) = flag(1,1)+1;
    end
    if PASS(i,2) == 2
        flag(1,2) = flag(1,2)+1;
    end
    if PASS(i,2) == 1
        flag(1,3) = flag(1,3)+1;
    end
end   
end