%%%%%%%%%%%%%%%%%%%%%%%%%%
% 左右脚传球数据对比
% 2018-11-20
%%%%%%%%%%%%%%%%%%%%%%%%%%
% clc; clear; close all;
% pathname = 'G:\275\';
% sensor_R = 'sensor-R.txt'; sensor_L = 'sensor-L.txt'; gps_L = 'gps-L.txt';
% % 添加路径
% addpath(genpath(pathname)); 
% % Sensor
% Sensor_R = importdata(sensor_R)/1000; Sensor_L = importdata(sensor_L)/1000; 
% Sensor_R(:,4:5) = Sensor_R(:,4:5)*1000; Sensor_L(:,4:5) = Sensor_L(:,4:5)*1000;
% gps = importdata(gps_L);
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function output = Total_ball(Sensor_R,Sensor_L,gps)
% 左右脚的触球数据
% pass = BALL(Sensor_R,Sensor_L,filterlat,filterlon,sensor_fs); BALL_Z(sensor_r,sensor_l,gps)
output = []; pass = BALL_Z(Sensor_R,Sensor_L,gps);
% 判断有没有数据
if isempty(pass) 
    output = [];
    return;
end
% 按照时间间隔排序
Total = sortrows(pass,[3 4]); % PASS = Total ;
[m,~] = size(Total); j = 1; PASS = []; PASS(j,:) = Total(1,:);
for i = 2:m
    switch  PASS(j,2)
        case 3 % 触球判断
            if Total(i,3) - PASS(j,3) < 2000
                [~,z] = max([Total(i,7),PASS(j,7)]);
                if z == 1
                    PASS(j,:) = Total(i,:);
                end
            else
                j = j+1;
                PASS(j,:) = Total(i,:);
            end
        case 2   % 短传判断
            if Total(i,3) - PASS(j,3) < 5000
                [~,z] = max([Total(i,7),PASS(j,7)]);
                if z == 1
                    PASS(j,:) = Total(i,:);
                end
           else
                j = j+1;
                PASS(j,:) = Total(i,:);
            end
        case 1    % 长传判断
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
% 纠正触球次数
% output = validation_touch(PASS,5); % 连续触球次数不得超过5次
output = Verification_speed(PASS,5,11.4);
% % 判断触球次数 
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
% title('触球速度柱状图');
% subplot(3,1,1)
% bar(speed1,'r'); xlabel('长传次数'); ylabel('m/s');
% subplot(3,1,2)
% bar(speed2,'k'); xlabel('短传次数'); ylabel('m/s');
% subplot(3,1,3)
% bar(speed3,'b'); xlabel('触球次数'); ylabel('m/s');
end