%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 2018-10-24
% 计算GPS两点之间的距离和方位角
%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function [distance,azimuth] = GPS_calculate(lat1,lon1,lat2,lon2)
    %% 数据处理
    pk = 180 /pi; R = 6372797;
    %%
    a1 = lat1/pk; a2 = lon1/pk; b1 = lat2/pk; b2 = lon2/pk;
    t1 = cos(a1) * cos(a2) * cos(b1) * cos(b2);
    t2 = cos(a1) * sin(a2) * cos(b1) * sin(b2);
    t3 = sin(a1) * sin(b1); tt = acos(t1 + t2 + t3);
    %% 距离计算
%     % 第一种方法
%     setGlobalParam();
%     distance= abs(GPSDist(lat1,lon1,lat2,lon2)); 
    % 第二种方法
    distance = R * tt;
    %% 方位角计算
    stt = sqrt(1-(cos(tt)^2));
    A = asin(cos(b1)*sin(b2-a2)/stt);
    if (b1>=a1&&b2>=a2)
        azimuth = A*pk;
    end
    if (b1>=a1&&b2<a2)
        azimuth = A*pk+360;
    end
    if (b1<a1)
        azimuth = 180-A*pk;
    end  
end
