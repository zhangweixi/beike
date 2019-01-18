%%%%%%%%%%%%%%%%%%%%%%%%%%
% ���ҽŴ������ݶԱ�
% 2018-11-20
%%%%%%%%%%%%%%%%%%%%%%%%%%
% clc; clear; close all;
% pathname = 'G:\275\';
% sensor_R = 'sensor-R.txt'; sensor_L = 'sensor-L.txt'; gps_L = 'gps-L.txt';
% % ���·��
% addpath(genpath(pathname)); 
% % Sensor
% Sensor_R = importdata(sensor_R)/1000; Sensor_L = importdata(sensor_L)/1000; 
% Sensor_R(:,4:5) = Sensor_R(:,4:5)*1000; Sensor_L(:,4:5) = Sensor_L(:,4:5)*1000;
% gps = importdata(gps_L);
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function output = Total_ball(Sensor_R,Sensor_L,gps)
% ���ҽŵĴ�������
% pass = BALL(Sensor_R,Sensor_L,filterlat,filterlon,sensor_fs); BALL_Z(sensor_r,sensor_l,gps)
output = []; pass = BALL_Z(Sensor_R,Sensor_L,gps);
% �ж���û������
if isempty(pass) 
    output = [];
    return;
end
% ����ʱ��������
Total = sortrows(pass,[3 4]); % PASS = Total ;
[m,~] = size(Total); j = 1; PASS = []; PASS(j,:) = Total(1,:);
for i = 2:m
    switch  PASS(j,2)
        case 3 % �����ж�
            if Total(i,3) - PASS(j,3) < 2000
                [~,z] = max([Total(i,7),PASS(j,7)]);
                if z == 1
                    PASS(j,:) = Total(i,:);
                end
            else
                j = j+1;
                PASS(j,:) = Total(i,:);
            end
        case 2   % �̴��ж�
            if Total(i,3) - PASS(j,3) < 5000
                [~,z] = max([Total(i,7),PASS(j,7)]);
                if z == 1
                    PASS(j,:) = Total(i,:);
                end
           else
                j = j+1;
                PASS(j,:) = Total(i,:);
            end
        case 1    % �����ж�
            if Total(i,3) - PASS(j,3) < 10000
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
% �����������
% output = validation_touch(PASS,5); % ��������������ó���5��
output = Verification_speed(PASS,5,11.4);
% % �жϴ������ 
% flag = zeros(1,3); speed3 = []; speed2 = []; speed1 = [];
% for i = 1:length(output)
%     if output(i,2) == 3 
%         flag(1,1) = flag(1,1)+1;
%         speed3(flag(1,1)) = output(i,7);
%     end
%     if output(i,2) == 2
%         flag(1,2) = flag(1,2)+1;
%         speed2(flag(1,2)) = output(i,7);
%     end
%     if output(i,2) == 1
%         flag(1,3) = flag(1,3)+1;
%         speed1(flag(1,3)) = output(i,7);
%     end
% end  
% figure
% title('�����ٶ���״ͼ');
% subplot(3,1,1)
% bar(speed1,'r'); xlabel('��������'); ylabel('m/s');
% subplot(3,1,2)
% bar(speed2,'k'); xlabel('�̴�����'); ylabel('m/s');
% subplot(3,1,3)
% bar(speed3,'b'); xlabel('�������'); ylabel('m/s');
end