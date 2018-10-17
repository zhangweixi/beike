%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% GPS ���ݴ���
% 2018-09-04
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function [GPS_result,filter_lat,filter_lon] = GPS_handle(LAT,LON,Lat_Data,Lon_Data,fs,HZ)
%% ȥ������
k = 1;
for i = 1:length(LAT)
    if (LAT(i) < 31 || LAT(i) > 32)
        flag = false;
    elseif (LON(i) < 121 || LON(i) > 122)
        flag = false;
    else
        X(k) = LAT(i); Y(k) = LON(i);
        k = k+1;
    end    
end
X = X';Y = Y';
%% KRR�ع�
%lambda = 1E-10;		% regularization constant
kernal_type = 'gauss';	% kernel type
sigma = 2*HZ+1;		% Gaussian kernel width
filterlat = GPS_My_KRR(X,fs,HZ,Lat_Data,kernal_type,sigma);
%lambda = 1E-10;		% regularization constant
filterlon = GPS_My_KRR(Y,fs,HZ,Lon_Data,kernal_type,sigma);
%% ���GPS����Ƶ�ʣ�Ĭ��ֵΪ100Hz��

filter_lat = RBF_resample(filterlat,HZ,100)'; filter_lon = RBF_resample(filterlon,HZ,100)';

%% �ٶ�v�����ٶ�a��Ծ��J
setGlobalParam();
% time
Time = numel(filter_lat)/HZ;
% GPS distance
for i = 1:numel(filter_lat)-1
    dist(i)= abs(GPSDist(filter_lat(i), filter_lon(i), filter_lat(i+1), filter_lon(i+1))); 
    Distance = dist;   
end
N = length(Distance);
Output_Distance = sum(Distance); % ����S
time = Time; % ʱ��
Output_V = Distance*HZ; % �ٶ�
V = Output_Distance/time; % ƽ���ٶ�V
% time = linspace(1/(2*M),Time-1/(2*M),numel(Output_V));
Output_A = diff(Distance,1)*HZ^2; % ���ٶ�A
A = mean(abs(Output_A)); % ƽ�����ٶ�
% time = linspace(1/M,Time-1/M,numel(Output_A));
% GPS_result = struct{'distance':Output_Distance,'"time"':time,'"velocity"':Output_V,'"mean_velocity"',V,'"accelerate"',Output_A,'"mean_accelerate"',A};
Output_T = 1/HZ:1/HZ:time-2/HZ;
result1 = [V,Output_T]';result2 = [A,Output_V(2:N)]';result3 = [Output_Distance,Output_A]';
W = [0;filter_lat(2:N)];J = [0;filter_lon(2:N)];
GPS_result = [result1,result2,result3,W,J];
end

