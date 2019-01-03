%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% GPS数据前处理
% 2018-10-24
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function GPS = GPS_pretreatment(gps)
%% 去除误差点
k = 1; X = []; Y = [];
for i = 1:length(gps)
    if (gps(i,1) == 0)
        flag = false;
    elseif (gps(i,2) == 0)
        flag = false;
    else
%         LAT = fix(gps(i,1)); X(k) = LAT+(gps(i,1)-LAT)*100/60; 
%         LON = fix(gps(i,2)); Y(k) = LON+(gps(i,2)-LON)*100/60;
        X(k) = gps(i,1); Y(k) = gps(i,2);
        k = k+1;
    end    
end
% 判断有没有GPS
if isempty(X)
    GPS = []; return;
end
GPS = [X',Y']; % 去除误差后的原始数据
end