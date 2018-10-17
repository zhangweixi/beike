%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% LANQI
% 2018-09-05
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function LanQi(pathname,sensor_R,sensor_L,compass_R,compass_L,gps_L,gpsresult,passresult,stepnumber)
% �������ݸ�ʽ���������ݵ�ַ��
% sensor ���ݸ�ʽΪ{'sensor_R':[xdata;ydata;zdata],'sensor_L':[xdata;ydata;zdata]}
% compass ���ݸ�ʽΪ{'compass_R':[],'compass_L':[]} �� GPS ���ݸ�ʽΪ{'GPS_lat':[],'GPS_lon',[]}
% ������ݸ�ʽ��
% GPS_result ���ݸ�ʽΪ{'"distance"',Output_Distance,'"time"',time,'"velocity"',Output_V,'"mean_velocity"',V,'"accelerate"',Output_A,'"mean_accelerate"',A}
% BD ���ݸ�ʽΪ {'lat',[],'lon',[]}��
% STEP ���ݸ�ʽΪ {'Time_R',Time_R,'Amplitude_R',Amplitude_R,'number_R','Time_L',Time_L,'Amplitude_L',Amplitude_L,'number_L',number_L}
%% ������

% Detect
% pathname = 'G:\870\870';sensor_R = 'sensor-R.txt';sensor_L = 'sensor-L.txt';
% compass_R = 'angle-L.txt';compass_L = 'angle-R.txt';gps_L = 'gps-L.txt';
% gpsresult = 'gpsresult.txt';passresult = 'passresult.txt';stepnumber = 'stepnumber.txt';

% ���·��
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
%% �ز���
M = 100;
% compass �����ز���
fs = 25;
CP_R = RBF_resample(Compass_R,fs,M); CP_L = RBF_resample(Compass_L,fs,M);
% GPS �����ز���
fs = 10;Re_fs = 1;
GPS = RBF_resample(gps,fs,Re_fs);
%% GPS ���ݴ���
HZ = 2;
% [Lat_Data,Lon_Data] = Cross_lambda(GPS(:,1),GPS(:,2),fs,M);
[GPS_result,filter_lat,filter_lon] = GPS_handle(GPS(:,1),GPS(:,2),1E-10,1E-10,Re_fs,HZ);
%% sensor ���ݴ���
% ���㲽��
[~,~,number_R] = step(Sensor_R,M,'norm');
[~,~,number_L] = step(Sensor_L,M,'norm');
%% �������ͣ���һ�д������ҽ�1-�ҽš�0-���,�ڶ��д�������״̬��1-������2-�̴���3-����
pass = BALL(Sensor_R,Sensor_L,filter_lat,filter_lon,HZ,M);
%% �����ݵ�ָ���ļ���
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



