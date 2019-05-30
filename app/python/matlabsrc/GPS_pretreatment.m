%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% GPS数据前处理
% 2018-10-24
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function GPS = GPS_pretreatment(gps)
%% 去除两端误差点
[m,~] = size(gps); k = 1; M = 1; X = []; Y = [];
while (gps(k,1) == 0)||(gps(k,2) == 0) % 去掉开头的零点
    k = k+1;
end
if k == m
    GPS = [];
    return;
end
while (gps(m,1) == 0)||(gps(m,2) == 0) % 去掉结尾的零点
    m = m-1;
end
for i = k:m
    X(M) = gps(i,1); Y(M) = gps(i,2);
    M = M+1;
end
%% 去除中间误差点
gps = [X',Y']; [n,~] = size(gps); i = 1; 
while i <= n
    flag = 0;
    while (gps(i,1) == 0)||(gps(i,2) == 0)
        flag = flag+1; i = i+1;
    end
    if flag == 0 % 没有奇异值
        X(i) = gps(i,1); Y(i) = gps(i,2);
    else
        interval_lat = (gps(i,1)-gps(i-flag-1,1))/(flag+1); 
        interval_lon = (gps(i,2)-gps(i-flag-1,2))/(flag+1);
        for j = 0:flag
             X(i-j) = gps(i,1)-interval_lat*j; 
             Y(i-j) = gps(i,2)-interval_lon*j;
        end
    end
    i = i+1; 
end
% k = 1; X = []; Y = [];
% for i = 1:length(gps)
%     if (gps(i,1) == 0)
%         flag = false;
%     elseif (gps(i,2) == 0)
%         flag = false;
%     else
%         X(k) = gps(i,1); Y(k) = gps(i,2);
%         k = k+1;
%     end    
% end
% 判断有没有GPS
if isempty(X)
    GPS = []; return;
end
GPS = [X',Y'];
end