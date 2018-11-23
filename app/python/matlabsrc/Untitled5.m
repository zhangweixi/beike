%%%%%%%%%%%%%%%%%%%%%%%%%
% 判断触球状态
% 2018-09-05
%%%%%%%%%%%%%%%%%%%%%%%%%
% 数据类型：第一列代表左右脚1-右脚、0-左脚
% 第二列代表有球状态：1-长传、2-短传、3-触球。
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
clc; clear all;
pathname = 'D:\实习项目-步态识别\Data ';
sensor_R = 'sensor-R.txt'; sensor_L = 'sensor-L.txt'; gps_L = 'gps-L.txt';
angle_R = 'angle-R.txt'; angle_L = 'angle-L.txt'; 
% 添加路径
addpath(genpath(pathname)); 
% Sensor
sensor_r = importdata(sensor_R)/1000; Time = 1/100:1/100:length(sensor_r)/100;
sensor_l = importdata(sensor_L)/1000; 
%%
% Compass
% Compass_R = importdata(angle_R); Compass_L = importdata(angle_L); 
% time = 1/40:1/40:length(Compass_R)/40;
% figure ; plot(time,Compass_R(:,1:3))
%  
% figure;  plot(Time,sensor_r(:,1:3));

%%
fs = 100; lat = 0; n_r = length(sensor_r);
for i = 1:n_r
    SMA_R(i) = (sensor_r(i,2)^2+sensor_r(i,3)^2)^1/2;
    A(i) = (sensor_r(i,1)^2+sensor_r(i,2)^2+sensor_r(i,3)^2)^1/2;
end
singular = error_ellipse3(sensor_r(:,1),sensor_r(:,2),sensor_r(:,3),0.999); % 第一次筛选
singular(:,5) = SMA_R(singular(:,1));
D = zeros(n_r,1); D(singular(:,1)) = A(singular(:,1));
output = vibrate(D,9 ,100,300); % 第二次筛选
% 判断有没有数据
if isempty(output)
    Output = null;
    return;
end
% 第三次选择
i = 1; k = 1;
while i<=length(output)
    if output(i,1)+50 <= n_r
        B = find(D(output(i,1)-50 : output(i,1)+50)~=0)+output(i,1)-51;
    else
        B = find(D(output(i,1)-50 : n_r) ~= 0)+output(i,1)-51;
    end
    M = mean(D(B));
    if length(B) < 10
        S = 10*std(D(B)); 
    end
    if length(B) > 16
        S = 0;
    else
        switch length(B)
            case 10
               S = 8*std(D(B));
            case 11
               S = 4*std(D(B));
            case 12
               S = 2*std(D(B));     
            case 13
               S = std(D(B));    
            case 14
               S = 1/2*std(D(B));  
            case 15
               S = 1/4*std(D(B));  
            case 16
               S = 1/8*std(D(B));  
        end
    end
    if  output(i,2) > M+S
        Output(k,:) = output(i,:);
        k = k+1;
    end
    i = i+1;
end