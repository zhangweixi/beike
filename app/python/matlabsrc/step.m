%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 计算步数
% 2018-07-31
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%% 读数据
% clc; clear all;
% pathname = 'G:\data';sensor_R = 'sensor-R.txt';
% % Sensor
% addpath(genpath(pathname)); data = importdata(sensor_R)/1000; 
% fs = 100; 
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function output = step(data,fs)
% 判断data有没有数据
if isempty(data)
    output = [];
    return;
end
[n,~] = size(data);
for i = 1:n
    SMA(i) = sqrt(data(i,1)^2+data(i,2)^2+data(i,3)^2);
end
singular = error_ellipse3(data(:,1),data(:,2),data(:,3),0.999);
singular(:,5) = SMA(singular(:,1));
% 判断有没有步数
[m,~] = size(singular); Singular = []; k = 1;
for j = 1:m
    if singular(j,5) > 2.5
        Singular(k,1) = singular(j,1)/fs;
        Singular(k,2) = singular(j,5);
        k = k+1;
    end
end 
if isempty(Singular)
    output = [];
    return;
end
% 延迟机制
if k < 3
    output = Singular;
else
    output = []; j = 1; output(j,:) = Singular(1,:);
    for i = 2:k-1
        if  Singular(i,1)-output(j,1) < 0.33
            [~,m] = max([Singular(i,2) output(j,2)]);
            if m == 1
               output(j,:) = Singular(i,:);
            end
        else
            j = j+1;
            output(j,:) = Singular(i,:);
        end
    end
end


