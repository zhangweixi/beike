%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% KRR regularization
% 2018-07-29
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function [filter_lat] = GPS_My_KRR(lat,fs,M,lambda,kernal_type,sigma)
%% PARAMETERS
% lambda 		% regularization constant
% kernel 	    % kernel type
% sigma 		% Gaussian kernel width
kernel = kernal_type;
m = max(size(lat));
t = m/fs;
time = linspace(0,t,m)';
n = fix(M*t);
Time = linspace(0,t,n)';
%% PROGRAM
[~,filter_lat] = km_krr(time,lat,kernel,sigma,lambda,Time);	
end
