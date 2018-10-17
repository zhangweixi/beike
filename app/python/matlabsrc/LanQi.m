%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% LANQI
% 2018-09-05
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function LanQi(pathname,sensor_R,sensor_L,compass_R,compass_L,gps_L,gpsresult,passresult,stepnumber)
% 输入数据格式：都是数据地址。
% sensor 数据格式为{'sensor_R':[xdata;ydata;zdata],'sensor_L':[xdata;ydata;zdata]}
% compass 数据格式为{'compass_R':[],'compass_L':[]} ； GPS 数据格式为{'GPS_lat':[],'GPS_lon',[]}
% 输出数据格式：
% GPS_result 数据格式为{'"distance"',Output_Distance,'"time"',time,'"velocity"',Output_V,'"mean_velocity"',V,'"accelerate"',Output_A,'"mean_accelerate"',A}
% BD 数据格式为 {'lat',[],'lon',[]}；
% STEP 数据格式为 {'Time_R',Time_R,'Amplitude_R',Amplitude_R,'number_R','Time_L',Time_L,'Amplitude_L',Amplitude_L,'number_L',number_L}
%% 读数据

% Detect
% pathname = 'G:\870\870';sensor_R = 'sensor-R.txt';sensor_L = 'sensor-L.txt';
% compass_R = 'angle-L.txt';compass_L = 'angle-R.txt';gps_L = 'gps-L.txt';
% gpsresult = 'gpsresult.txt';passresult = 'passresult.txt';stepnumber = 'stepnumber.txt';

% 添加路径
addpath(genpath(pathname)); 
% sensor % filename = pathsensor_r;% A = 'sensor-R.txt';
Sensor_R = importdata(sensor_R)/1000;  
% filename = pathsensor_l; % A = 'sensor-L.txt';
Sensor_L = importdata(sensor_L)/1000; 
% compass % filename = pathcompass_r;% A = 'compass-R.txt';
Compass_R = importdata(compass_R); 
% filename = pathcompass_l;% A = 'compass-L.txt';
Compass_L = importdata(compass_L); 
% GPS % filename = pathgps;% A = 'gps-L.txt';
gps = importdata(gps_L)/100; 
%% 重采样
M = 100;
% compass 数据重采样
fs = 25;
CP_R = RBF_resample(Compass_R,fs,M); CP_L = RBF_resample(Compass_L,fs,M);
% GPS 数据重采样
fs = 10;Re_fs = 1;
GPS = RBF_resample(gps,fs,Re_fs);
%% GPS 数据处理
HZ = 2;
% [Lat_Data,Lon_Data] = Cross_lambda(GPS(:,1),GPS(:,2),fs,M);
[GPS_result,filter_lat,filter_lon] = GPS_handle(GPS(:,1),GPS(:,2),1E-10,1E-10,Re_fs,HZ);
%% sensor 数据处理
% 计算步数
[~,~,number_R] = step(Sensor_R,M,'norm');
[~,~,number_L] = step(Sensor_L,M,'norm');
%% 数据类型：第一列代表左右脚1-右脚、0-左脚,第二列代表有球状态：1-长传、2-短传、3-触球。
pass = BALL(Sensor_R,Sensor_L,filter_lat,filter_lon,HZ,M);
%% 存数据到指定文件夹
% filename = [savetime_r,'.txt']; 
B = [pathname,stepnumber];
dlmwrite(B,[number_R,number_L],'delimiter','\t','newline','pc');
% pathname = 'G:\548\';gpsresult = 'GPS_result.txt';
B = [pathname,gpsresult];
dlmwrite(B,GPS_result,'precision', '%.6f','delimiter',' ','newline','pc');
% pathname = 'G:\548\';passresult = 'pass.txt';
B = [pathname,passresult];
dlmwrite(B,pass,'precision', '%.6f','delimiter',' ','newline','pc');
end 



