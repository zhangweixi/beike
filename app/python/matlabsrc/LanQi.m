%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% LANQI ������
% 2018-09-05
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function LanQi(pathname,sensor_R,sensor_L,angle_R,angle_L,gps_L,court_config,gpsresult,turnresult,passresult,stepresult,shootresult,website)
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% �����ʽ
% LanQi('�ļ�����λ��','sensor-R.txt','sensor-L.txt','angle-R.txt','angle-L.txt',
% 'gps-L.txt','court-config.txt','gpsresult.txt','turnresult.txt','passresult.txt',
% 'stepresult.txt','shootresult.txt','��ַ')
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% ���·��
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
%% ����Ƶ��
gps_fs = 10; compass_fs = 40; sensor_fs = 100;

%% GPS ���ݴ���
[GPS_result,~,~] = GPS_handle(gps,gps_fs);

%% ת��
turn_result = Turn(gps,3);

%% sensor ���ݴ���
% ���㲽��
Step_result =  Step_calculate(Sensor_R,Sensor_L,sensor_fs);

% �������ݣ���һ�д������ҽ�1-�ҽš�0-���,�ڶ��д�������״̬��1-������2-�̴���3-����
pass = Total_ball(Sensor_R,Sensor_L,gps);

% ��������
shoot_result = Shoot_Z(pass,Compass_R,Compass_L,compass_fs,Court_config);

% ȥ�������е���������
Pass = DIV(pass,shoot_result);

%% �����ݵ�ָ���ļ���
% ����
B = [pathname,stepresult];
dlmwrite(B,real(Step_result),'delimiter','\t','newline','pc');

% GPS���ݴ���
B = [pathname,gpsresult];
dlmwrite(B,real(GPS_result),'precision', '%.6f','delimiter',' ','newline','pc');

% ת��
B = [pathname,turnresult];
dlmwrite(B,real(turn_result),'precision', '%.6f','delimiter',' ','newline','pc');

% ����
B = [pathname,passresult];
dlmwrite(B,real(Pass),'precision', '%.6f','delimiter',' ','newline','pc');

% ����
B = [pathname,real(shootresult)];
dlmwrite(B,real(shoot_result),'precision', '%.6f','delimiter',' ','newline','pc');

% ��ַ
webread(website);

end 



