% clc; clear all;
% pathname = 'G:\data';
% gps_L = 'gps-L.txt';
% % 添加路径
% addpath(genpath(pathname)); 
% % GPS
% gps = importdata(gps_L)/100; time = 3;
function  turn_result = Turn(gps,time)
%% GPS 预处理
gps = GPS_pretreatment(gps); [z,~] = size(gps);

%%
interval = round(time*10/3); turn_result = [];
i = 1; k = 1; kk = 1; flag = 1;
while i+3*interval-1 <= z
    D1 = mean(gps(i:i+interval-1,:)); D2 = mean(gps(i+interval:i+2*interval-1,:)); D3 = mean(gps(i+2*interval:i+3*interval-1,:));
    [~,azimuth1] = GPS_calculate(D1(1),D1(2),D2(1),D2(2));
    [~,azimuth2] = GPS_calculate(D2(1),D2(2),D3(1),D3(2));
    azimuth = abs(azimuth2-azimuth1);
    if azimuth > 180
       azimuth = 360-azimuth;
    end
    if azimuth<=45
        i = i+1;
    else
        if (azimuth>45) && (azimuth<120)
            turn_result(flag,:) = [1,azimuth1,azimuth2,D2(1),D2(2)];
            i = i+3*interval; flag = flag+1;
        else
            turn_result(flag,:) = [2,azimuth1,azimuth2,D2(1),D2(2)];
            i = i+3*interval; flag = flag+1;
        end
    end    
end
end