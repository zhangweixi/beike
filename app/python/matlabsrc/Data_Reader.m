clear all; clc; close all;
%addpath('E:\phpstudy\PHPTutorial\WWW\api.launchever.cn\public');
addpath('C:\企业管理材料\上海澜启信息科技有限公司\数据分析代码\jsonlab-1.5');
json = urlread('http://dev.api.launchever.cn/sensor-3.json');
tic
fid = fopen('sensor.json','w');
fprintf(fid,'%s',json);
fclose(fid);
xdata = [];ydata = [];zdata = [];
jsondata = cell({'"ax"',xdata,'"gy"',ydata,'"gz"',zdata});
jsondata = loadjson('sensor.json');
fs = 400;
dt = 1/fs;
toc
figure
plot(dt.*[1:length(jsondata.ax)],jsondata.ax);
title('X-Signal');
xlabel('time/s');
ylabel('Amplitude');
figure
plot(dt.*[1:length(jsondata.ax)],jsondata.ay);
title('Y-Signal');
xlabel('time/s');
ylabel('Amplitude');
figure
plot(dt.*[1:length(jsondata.ax)],jsondata.az);
title('Z-Signal');
xlabel('time/s');
ylabel('Amplitude');

