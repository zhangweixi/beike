%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% GPS ���ٶ�
% 2018-07-30
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function [Output_Distance,Output_V,Output_A,Output_J,BD] = Output(lat,lon,M)
setGlobalParam();
%% time
Time = numel(lat)/M;
%% GPS translation the baidu
for i = 1:numel(lat)
    [Gcj02,flag] = gps84_To_Gcj02(lat(i),lon(i),(i-1)/M); 
    if (flag)
       bd = gcj02_To_Bd09(Gcj02.Lat, Gcj02.Lon);
       BD(i,:) = bd;
    end
end
%% GPS distance
for i = 1:numel(lat)-1
    dist(i)= abs(GPSDist(lat(i), lon(i), lat(i+1), lon(i+1))); 
    Distance = dist;   
end
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ����S
Output_Distance = sum(Distance);
time = Time;
v = Output_Distance/time;
fprintf('���� %.9f :ʱ�� %.9f :ƽ���ٶ� %.9f\n\n',Output_Distance,time,v);
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% �ٶ�V
Output_V = Distance*M;
time = linspace(1/(2*M),Time-1/(2*M),numel(Output_V));
figure
plot(time,Output_V,'b');
xlabel('ʱ��/s');ylabel('�ٶ�/m/s');
title('GPS����');grid on
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% ���ٶ�A
Output_A = diff(Distance,1)*M^2; 
time = linspace(1/M,Time-1/M,numel(Output_A));
figure
plot(time,Output_A,'r');
xlabel('ʱ��/s');ylabel('���ٶ�/m/s');
title('GPS����ٶ�');grid on
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%% Ծ��J
Output_J = diff(Distance,2)*M^3; 
time = linspace(3/(2*M),Time-3/(2*M),numel(Output_J ));
figure
plot(time,Output_J ,'g');
xlabel('ʱ��/s');ylabel('Ծ��/m/s');
title('GPS��Ծ��');grid on     
end