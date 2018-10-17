%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 交叉验证lambda
% 2018-07-31
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function [Lat_Data,Lon_Data] = Cross_lambda(lat,lon,fs,M)
%% 去除误差点
k = 1;
for i = 1:length(lat)
    if (lat(i) < 31 || lat(i) > 32)
        flag = false;
    elseif (lon(i) < 121 || lon(i) > 122)
        flag = false;
    else
        X(k) = lat(i); Y(k) = lon(i);
        k = k+1;
    end    
end
X = X';Y = Y';
%%
index = -linspace(12,8,5);
kernal_type = 'gauss';	% kernel type
sigma = 2*M+1;		% Gaussian kernel width
net = feedforwardnet(10);
for i = 1:numel(index)
    lambda(i) = 10^index(i);
    %
    [filter_lat] = GPS_My_KRR(X,fs,M,lambda(i),kernal_type,sigma);
    [Relat_sample,~] = Inter(X,fs,M);
    lat_perf(i) = mse(net,filter_lat,Relat_sample,'regularization',1E-17);
    lat_data{i} = [lambda(i),lat_perf(i)];
    %
    [filter_lon] = GPS_My_KRR(Y,fs,M,lambda(i),kernal_type,sigma);
    [Relon_sample,~] = Inter(Y,fs,M);
    lon_perf(i) = mse(net,filter_lon,Relon_sample,'regularization',1E-17);
    lon_data{i} = [lambda(i),lon_perf(i)];
end
[~,n] = min(lat_perf); Lat_Data = lat_data{n};
[~,n] = min(lon_perf); Lon_Data = lon_data{n};
end