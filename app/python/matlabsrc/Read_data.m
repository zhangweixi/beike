%%%%%%%%%%%%%%%%%%%%%%%%%
% 数据读取
% 2018-09-07
%%%%%%%%%%%%%%%%%%%%%%%%%
function [R,Compass_R,Sensor_L,Compass_L,GPS] = Read_data(A)
% 输入数据为文件夹名称 sensor_R,compass_R,sensor_L,compass_L,gps
addpath('A');
%% sensor
R = [];
fid = fopen('Sensor_R','r'); fprintf(fid,'%s',R);fclose(fid);
fid = fopen('sensor_L','w'); Sensor_L = fscanf(fid,'%s');fclose(fid);
%% GPS 
fid = fopen('gps_L','w'); GPS = fscanf(fid,'%s')/100; fclose(fid);
%% compass
fid = fopen('compass_R','w'); Compass_R =  fscanf(fid,'%s'); fclose(fid);
fid = fopen('compass_L','w'); Compass_L =  fscanf(fid,'%s');fclose(fid);
end