%%%%%%%%%%%%%%%%%%%%%%%%
% 判断球门位置
% 2018-11-30
%%%%%%%%%%%%%%%%%%%%%%%%
function [LAT,LON,Distance,Angle,C] = Goal(lat,lon,court)
if (nargin < 3)
    error('Input error');
end
if isempty(court)
    LAT = []; LON = []; C = [];
    Distance = []; Angle = [];
    return;
end
%% 判断球门位置
[m,~] = size(court); DIS = []; k = 1;
for i = m-1:m
    Lat = (court(i,1)+court(i,3))/2; Lon = (court(i,2)+court(i,4))/2; 
    [distance,~] = GPS_calculate(lat,lon,Lat,Lon);
    DIS(k) = distance; k = k+1;
end
[~,W] = min(DIS);
if W == 1
    M = m-1;
else
    M = m;
end
LAT = (court(M,1)+court(M,3))/2; LON = (court(M,2)+court(M,4))/2; % 球门中心
[distance1,azimuth1] = GPS_calculate(lat,lon,court(M,1),court(M,2)); % 左球门柱
[distance2,azimuth2] = GPS_calculate(lat,lon,court(M,3),court(M,4)); % 右球门柱   
%% 计算相应的参数
C = [azimuth1,azimuth2];
Distance = (distance1+distance2)/2;
Angle = (azimuth1+azimuth2)/2;
end