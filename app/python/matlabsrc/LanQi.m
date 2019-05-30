%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% LANQI 主程序
% 2018-09-05
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function LanQi(pathname,sensor_R,sensor_L,angle_R,angle_L,gps_L,court_config,gpsresult,turnresult,passresult,stepresult,shootresult,website)
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 输入格式
% LanQi('文件所在位置','sensor-R.txt','sensor-L.txt','angle-R.txt','angle-L.txt',
% 'gps-L.txt','court-config.txt','gpsresult.txt','turnresult.txt','passresult.txt',
% 'stepresult.txt','shootresult.txt','网址')
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 添加路径
addpath(genpath(pathname)); 
% Sensor
Sensor_R = importdata(sensor_R)/1000; Sensor_L = importdata(sensor_L)/1000;
Sensor_R(:,4:5) = Sensor_R(:,4:5)*1000; Sensor_L(:,4:5) = Sensor_L(:,4:5)*1000;
% Compass
Compass_R = importdata(angle_R); Compass_L = importdata(angle_L); 
% GPS
gps = importdata(gps_L); 
% Court
Court_config = importdata(court_config);
%% 采样频率
gps_fs = 10; compass_fs = 40; sensor_fs = 100;

%% GPS 数据处理
[GPS_result,~,~] = GPS_handle(gps,gps_fs);

%% 转向
turn_result = Turn(gps,3);

%% sensor 数据处理
% 计算步数
Step_result =  Step_calculate(Sensor_R,Sensor_L,sensor_fs);

% 触球数据：第一列代表左右脚1-右脚、0-左脚,第二列代表有球状态：1-长传、2-短传、3-触球。
pass = Total_ball(Sensor_R,Sensor_L,gps);

% 射门数据
shoot_result = Shoot_Z(pass,Compass_R,Compass_L,compass_fs,Court_config);

% 去掉传球中的射门数据
Pass = DIV(pass,shoot_result);

%% 存数据到指定文件夹
% 步数
B = [pathname,stepresult];
dlmwrite(B,real(Step_result),'delimiter','\t','newline','pc');

% GPS数据处理
B = [pathname,gpsresult];
dlmwrite(B,real(GPS_result),'precision', '%.6f','delimiter',' ','newline','pc');

% 转向
B = [pathname,turnresult];
dlmwrite(B,real(turn_result),'precision', '%.6f','delimiter',' ','newline','pc');

% 传球
B = [pathname,passresult];
dlmwrite(B,real(Pass),'precision', '%.6f','delimiter',' ','newline','pc');

% 射门
B = [pathname,real(shootresult)];
dlmwrite(B,real(shoot_result),'precision', '%.6f','delimiter',' ','newline','pc');

% 网址
webread(website);

end 



