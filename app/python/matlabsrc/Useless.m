clc; clear; close all;
LanQi('G:\1301\','sensor-R.txt','sensor-L.txt','angle-R.txt','angle-L.txt','gps-L.txt','court-config.txt','gpsresult.txt','turnresult.txt','passresult.txt','stepresult.txt','shootresult.txt','123');
% ½á¹û
pathname = 'G:\1288';
passresult = 'passresult.txt'; gpsresult = 'gpsresult.txt';
stepresult = 'stepresult.txt'; turnresult = 'turnresult.txt'; shootresult = 'shootresult.txt';
addpath(genpath(pathname)); 
passresult = importdata(passresult); gpsresult = importdata(gpsresult);
stepresult = importdata(stepresult); turnresult = importdata(turnresult);
shootresult = importdata(shootresult);