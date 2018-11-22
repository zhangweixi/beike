%%%%%%%%%%%%%%%%%%%%%%%%%
% 判断触球状态
% 2018-09-05
%%%%%%%%%%%%%%%%%%%%%%%%%
% 数据类型：第一列代表左右脚1-右脚、0-左脚
% 第二列代表有球状态：1-长传、2-短传、3-触球。
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% clc; close all;
% pathname = 'G:\data';
% sensor_R = 'sensor-R.txt'; sensor_L = 'sensor-L.txt'; gps_L = 'gps-L.txt';
% angle_R = 'angle-R.txt'; angle_L = 'angle-L.txt'; 
% % 添加路径
% addpath(genpath(pathname)); 
% % Sensor
% sensor_r = importdata(sensor_R)/1000; sensor_l = importdata(sensor_L)/1000;
% % GPS
% gps = importdata(gps_L); lat = gps(:,1); lon = gps(:,2); fs = 100;
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function pass = BALL(sensor_r,sensor_l,lat,lon,fs)
%% RIGHT
[output,n] = Touch(sensor_r,3,100,100,4); % 判断触球
% 判断有没有GPS     
if  isempty(lat)
    lat = zeros(n,2); lon = zeros(n,2);
end
% 判断有没有触球
if  ~isempty(output)
    [z,~] = size(output); pass_r_l = []; pass_r_s = []; pass_r_t = [];
    % 判断长传
    longpass3 = Long_pass(output,0.25,10,6,100,n); 
    if ~isempty(longpass3)
        [m,~] = size(longpass3);
        for i = 1:m
            time = longpass3(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
            pass_r_l(i,:) = [1 1 time longpass3(i,2) J W longpass3(i,3)];
        end
        output1 = []; k = 1; % 去掉长传
        while i <= z
            if (longpass3(:,1) ~= output(i,1))
               output1(k,:) = output(i,:);
               k = k+1;
            end
            i = i+1;
        end
    else
        output1 = output; longpass3 = zeros(1,6);
    end
    % 判断短传
    shortpass3 = Long_pass(output1,0.003,4,3,100,n); 
    if ~isempty(shortpass3)
        [m,~] = size(shortpass3);
        k = 1; j = 1;
        for i = 1:m
            if ~isempty(longpass3)
                M = min(abs(longpass3(:,1) - shortpass3(i,1)));
                if M < 1000 % 判断短传中的触球
                   time = shortpass3(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
                   pass_r_t(k,:) = [1 3 time shortpass3(i,2) J W shortpass3(i,3)];
                   k = k+1;
                else
                  time = shortpass3(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
                  pass_r_s(j,:) = [1 2 time shortpass3(i,2) J W shortpass3(i,3)];
                  j = j+1;
                end
            else
                time = shortpass3(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
                pass_r_s(j,:) = [1 2 time shortpass3(i,2) J W shortpass3(i,3)];
                j = j+1;
            end
        end
        [m,~] = size(output1);
        % 判断触球
        while i <= m 
            if (shortpass3(:,1) ~= output1(i,1))
                time = output1(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
                pass_r_t(k,:) = [1 3 time output1(i,2) J W output1(i,3)]; k = k+1;
            end
            i = i+1;
        end
    else
        [m,~] = size(output1);
        for i = 1:m
            time = output1(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
            pass_r_t(i,:) = [1 3 time output1(i,2) J W output1(i,3)];
        end
    end
    pass_r = [pass_r_l;pass_r_s;pass_r_t];
else
    pass_r = [];
end
% flag = 1; i = 1; [m,~] = size(output);
%     while i <= m
%        if output(i,5) > 0.3 
%            % 长传1
%            if output(i,6) > 0.3
%               time = output(i,1)/fs; 
%               pass_r(flag,:) = [1 1 time output(i,2) lat(round(time*10)) lon(round(time*10)) output(i,3)];
%               flag = flag+1; 
%            end 
%            % 短传2
%            if (output(i,6) <= 0.3)&&(output(i,6) >= 0.1)
%               time = output(i,1)/fs; 
%               pass_r(flag,:) = [1 2 time output(i,2) lat(round(time*10)) lon(round(time*10)) output(i,3)];
%               flag = flag+1;
%            end
%            % 触球3
%            if output(i,6) < 0.1
%               time = output(i,1)/fs; 
%               pass_r(flag,:) = [1 3 time output(i,2) lat(round(time*10)) lon(round(time*10)) output(i,3)];
%               flag = flag+1; 
%            end
%        end
%        i = i+1;
%     end
% else
%     pass_r = [];
% end
%% LEFT
[output,n] = Touch(sensor_l,20,500,100,21); % 判断触球
% 判断有没有GPS     
if  isempty(lat)
    lat = zeros(n,2); lon = zeros(n,2);
end
% 判断有没有触球
if  ~isempty(output)
    [z,~] = size(output); pass_l_l = []; pass_l_t = []; pass_l_s = [];
    % 判断长传
    longpass3 = Long_pass(output,0.25,10,6,100,n); 
    if ~isempty(longpass3)
        [m,~] = size(longpass3); pass_l_l = [];
        for i = 1:m
            time = longpass3(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
            pass_l_l(i,:) = [0 1 time longpass3(i,2) J W longpass3(i,3)];
        end
        % 去掉长传
        output1 = []; k = 1;
        while i <= z
            if (longpass3(:,1) ~= output(i,1))
               output1(k,:) = output(i,:);
               k = k+1;
            end
            i = i+1;
        end
    else
        output1 = output;
    end
    % 判断短传
    shortpass3 = Long_pass(output1,0.003,4,3,100,n); 
    if ~isempty(shortpass3)
        [m,~] = size(shortpass3); pass_l_t = []; pass_l_s = [];
        k = 1; j = 1;
        for i = 1:m
            if ~isempty(longpass3)
                M = min(abs(longpass3(:,1) - shortpass3(i,1)));
                if M < 1000 % 判断短传中的触球
                   time = shortpass3(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
                   pass_l_t(k,:) = [0 3 time shortpass3(i,2) J W shortpass3(i,3)];
                   k = k+1;
                else
                  time = shortpass3(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
                  pass_l_s(j,:) = [0 2 time shortpass3(i,2) J W shortpass3(i,3)];
                  j = j+1;
                end
            else
                time = shortpass3(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
                pass_l_s(j,:) = [0 2 time shortpass3(i,2) J W shortpass3(i,3)];
                j = j+1;
            end
        end
        [m,~] = size(output1);
        % 判断触球
        while i <= m 
            if (shortpass3(:,1) ~= output1(i,1))
                time = output1(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
                pass_l_t(k,:) = [0 3 time output1(i,2) J W output1(i,3)]; 
                k = k+1;
            end
            i = i+1;
        end
    else
        [m,~] = size(output1);
        for i = 1:m
            time = output1(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
            pass_l_t(i,:) = [0 3 time output1(i,2) J W output1(i,3)];
        end
    end
    pass_l = [pass_l_l;pass_l_s;pass_l_t];
else
    pass_l = [];
end
pass = [pass_r;pass_l];
% % 判断有没有触球
% if  ~isempty(output)
%     if  lat == 0
%         lat = zeros(n,2); lon = zeros(n,2);
% %     else
% %         z = length(lat);
% %         GPS_R = RBF_resample([lat,lon],fs,round(fs*n/z));        
%     end
%     flag = 1; i = 1; [m,~] = size(output); 
%     while i <= m
%        if output(i,5) > 0.3 
%            % 长传1
%            if (output(i,6) > 0.3)
%               time = output(i,1)/fs; 
%               pass_l(flag,:) = [0 1 time output(i,2) lat(round(time*10)) lon(round(time*10)) output(i,3)];
%               flag = flag+1; 
%            end 
%            % 短传2
%            if (output(i,6) <= 0.3)&&(output(i,6) >= 0.1)
%               time = output(i,1)/fs; 
%               pass_l(flag,:) = [0 2 time output(i,2) lat(round(time*10)) lon(round(time*10)) output(i,3)];
%               flag = flag+1;
%            end
%            % 触球3
%            if (output(i,6) < 0.1)
%               time = output(i,1)/fs; 
%               pass_l(flag,:) = [0 3 time output(i,2) lat(round(time*10)) lon(round(time*10)) output(i,3)];
%               flag = flag+1; 
%            end
%        end
%        i = i+1;
%     end
% else 
%     pass_l = []; 
% end
end


