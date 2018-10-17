%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% GPS ���ٶ�
% 2018-07-30
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function [output,time,BD] = Difference(lat,lon,M,order)
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
switch order
    case 0  % ����
        output = sum(Distance);
        time = Time;
        v = output/time;
        fprintf('���� %.9f :ʱ�� %.9f :ƽ���ٶ� %.9f\n\n',output,time,v);
    case 1  % �ٶ�V
        output = Distance*M;
        time = linspace(1/(2*M),Time-1/(2*M),numel(output));
        figure
        plot(time,output,'r');
        xlabel('ʱ��/s');ylabel('�ٶ�/m/s');
        title('GPS����');grid on
    case 2  % ���ٶ�A
        output = diff(Distance,1)*M^order; 
        time = linspace(1/M,Time-1/M,numel(output));
        figure
        plot(time,output,'r');
        xlabel('ʱ��/s');ylabel('���ٶ�/m/s');
        title('GPS����ٶ�');grid on
    case 3  % Ծ��J
        output = diff(Distance,2)*M^order; 
        time = linspace(3/(2*M),Time-3/(2*M),numel(output));
        figure
        plot(time,output,'r');
        xlabel('ʱ��/s');ylabel('Ծ��/m/s');
        title('GPS��Ծ��');grid on
end     
end