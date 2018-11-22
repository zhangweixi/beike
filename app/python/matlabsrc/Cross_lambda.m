%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 交叉验证lambda
% 2018-07-31
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function [Lat_Data,Lon_Data,X,Y] = Cross_lambda(lat,lon,fs,Re_fs)
%% 重采样
input = [lat,lon];
output = RBF_resample(input,fs,Re_fs);
X = output(:,1); Y = output(:,2);
%%
index = -linspace(15,5,11);
kernal_type = 'gauss';	% kernel type
sigma = 2*Re_fs+Re_fs;		% Gaussian kernel width
net = feedforwardnet(10);
for i = 1:numel(index)
    lambd(i) = 10^index(i);
    %
    [filter_lat] = GPS_My_KRR(X,Re_fs,fs,lambd(i),kernal_type,sigma);
    [Relat_sample,~] = Inter(X,Re_fs,fs);
    lat_perf(i) = mse(net,filter_lat,Relat_sample,'regularization',1E-19);
    lat_data{i} = [lambd(i),lat_perf(i)];
    %
    [filter_lon] = GPS_My_KRR(Y,Re_fs,fs,lambd(i),kernal_type,sigma);
    [Relon_sample,~] = Inter(Y,Re_fs,fs);
    lon_perf(i) = mse(net,filter_lon,Relon_sample,'regularization',1E-19);
    lon_data{i} = [lambd(i),lon_perf(i)];
end
[~,n] = min(lat_perf); Lat_Data = lat_data{n};
[~,n] = min(lon_perf); Lon_Data = lon_data{n}; 
end