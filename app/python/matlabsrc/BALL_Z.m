%%%%%%%%%%%%%%%%%%%%%%%%%
% ÅÐ¶Ï´¥Çò×´Ì¬
% 2018-09-05
%%%%%%%%%%%%%%%%%%%%%%%%%
% clc; clear all;
% pathname = 'G:\1129';
% sensor_R = 'sensor-R.txt'; sensor_L = 'sensor-L.txt'; gps_L = 'gps-L.txt';
% angle_R = 'angle-R.txt'; angle_L = 'angle-L.txt'; 
% % Ìí¼ÓÂ·¾¶
% addpath(genpath(pathname)); 
% % Sensor
% sensor_r = importdata(sensor_R)/1000; sensor_l = importdata(sensor_L)/1000;
% % GPS
% gps = importdata(gps_L)/100; lat = gps(:,1); lon = gps(:,2); fs = 100;
function pass = BALL_Z(sensor_r,sensor_l,lat,lon,fs)
%% RIGHT
[output,n] = Touch(sensor_r,3,100,100,4); % ÅÐ¶Ï´¥Çò
% ÅÐ¶ÏÓÐÃ»ÓÐ´¥Çò
if  ~isempty(output)
    [z,~] = size(output); pass_r_l = []; pass_r_s = []; pass_r_t = [];
    if  isempty(lat)
        lat = zeros(n,2); lon = zeros(n,2);
    end
    longpass3 = Long_pass(output,0.25,10,6,100,n); % ÅÐ¶Ï³¤´«
    if ~isempty(longpass3)
        [m,~] = size(longpass3);
        for i = 1:m
            time = longpass3(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
            pass_r_l(i,:) = [1 1 time longpass3(i,2) J W longpass3(i,3)];
        end
        % È¥µô³¤´«
        output1 = []; k = 1; i = 1;
        while i <= z
            if sum(longpass3(:,1) ~= output(i,1)) == length(longpass3(:,1))
                output1(k,:) = output(i,:);
                k = k+1;
            end
            i = i+1;
        end
    else
        output1 = output; 
    end
    [T,~] = size(output1);
    shortpass3 = Long_pass(output1,0.003,4,3,100,T); % ÅÐ¶Ï¶Ì´«
    if ~isempty(shortpass3)
        [m,~] = size(shortpass3); 
        for i = 1:m
            time = shortpass3(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
            pass_r_s(i,:) = [1 2 time shortpass3(i,2) J W shortpass3(i,3)];
        end
        % È¥µô¶Ì´«£¬ÅÐ¶ÏÎª´¥Çò
        k = 1; i = 1; [L,~] = size(output1);
        while i <= L
            if sum(shortpass3(:,1) ~= output1(i,1)) == length(shortpass3(:,1))
                time = output1(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
                pass_r_t(k,:) = [1 3 time output1(i,2) J W output1(i,3)];
                k = k+1;
            end
            i = i+1;
        end
    else
        [L,~] = size(output1);
        for i = 1:L
            time = output1(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
            pass_r_t(i,:) = [1 3 time output1(i,2) J W output1(i,3)];
        end       
    end
    pass_r = [pass_r_l;pass_r_s;pass_r_t];
else
    pass_r = [];
end
%% LEFT
[output,n] = Touch(sensor_l,20,500,100,21);
% ÅÐ¶ÏÓÐÃ»ÓÐ´¥Çò
if  ~isempty(output)
    [z,~] = size(output); pass_l_l = []; pass_l_s = []; pass_l_t = []; 
    if  lat == 0
        lat = zeros(n,2); lon = zeros(n,2);
    end
    longpass3 = Long_pass(output,0.25,10,6,100,n); % ÅÐ¶Ï³¤´«
    if ~isempty(longpass3)
        [m,~] = size(longpass3);
        for i = 1:m
            time = longpass3(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
            pass_l_l(i,:) = [0 1 time longpass3(i,2) J W longpass3(i,3)];
        end
        % È¥µô³¤´«
        output1 = []; k = 1; i = 1;
        while i <= z
            if sum(longpass3(:,1) ~= output(i,1)) == length(longpass3(:,1))
               output1(k,:) = output(i,:);
               k = k+1;
            end
            i = i+1;
        end
    else
        output1 = output;
    end
    shortpass3 = Long_pass(output1,0.003,4,3,100,n); % ÅÐ¶Ï¶Ì´«
    if ~isempty(shortpass3)
        [m,~] = size(shortpass3);
        for i = 1:m
            time = shortpass3(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
            pass_l_s(i,:) = [0 2 time shortpass3(i,2) J W shortpass3(i,3)];
        end
        % È¥µô¶Ì´«£¬ÅÐ¶ÏÎª´¥Çò
        k = 1; i = 1; [l,~] = size(output1);
        while i <= l
            if sum(shortpass3(:,1) ~= output1(i,1)) == length(shortpass3(:,1))
                time = output1(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
                pass_l_t(k,:) = [0 3 time output1(i,2) J W output1(i,3)];
                k = k+1;
            end
            i = i+1;
        end
    else
        [L,~] = size(output1);
        for i = 1:L
            time = output1(i,1)/fs; J = lat(round(time*10)); W = lon(round(time*10));
            pass_l_t(i,:) = [0 3 time output1(i,2) J W output1(i,3)];
        end   
    end
    pass_l = [pass_l_l;pass_l_s;pass_l_t];
else
    pass_l = [];
end
pass = [pass_r;pass_l];
% pass = unique(pass,'rows');
end


