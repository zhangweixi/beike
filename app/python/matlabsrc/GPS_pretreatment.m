%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% GPS����ǰ����
% 2018-10-24
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function GPS = GPS_pretreatment(gps)
%% ȥ������
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
% �ж���û��GPS
if isempty(X)
    GPS = []; return;
end
GPS = [X',Y']; % ȥ�������ԭʼ����
end