%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% GPS数据KRR
% 2018-07-27
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function [X_time,Y_time,X_alpha,Y_alpha] = KRR_GPS(time,x,y,lambda,sigma,kernel)
% N = 2500;	    % number of data points sampled from sinc
% N2 = 100;		% number of data points for testing the regression
% nvar = 0.05;	% noise variance factor
% kernel = 'gauss';	% kernel type
% sigma = 0.5;		% Gaussian kernel width
%% PROGRAM
% x = 6*(rand(N,1)-0.5);	% sampled data
% n = nvar*randn(N,1);	% noise
% y = 0*sin(3*x)./x+n+2*sin(6*x);		% noisy sinc data
% x2 = linspace(-3,3,N2)';	% input data for testing the regression

[X_alpha,X_time] = km_krr(time,x,kernel,sigma,lambda,time);	% regression weights alpha, and output y2 of the regression test
[Y_alpha,Y_time] = km_krr(time,y,kernel,sigma,lambda,time);	% regression weights alpha, and output y2 of the regression test
%%
% K = km_kernel(time,time,ktype,kpar);           % 求解K矩阵
% % [V,D] = eig(K); % 求矩阵特征值和特征向量
% ksx = my_ks_kernel(x,time,ktype,kpar);  % 求解小k
% X = ksx*inv(K+lambda*eye(size(K)))*x;
% %%
% ksy = my_ks_kernel(y,time,ktype,kpar);  % 求解小k
% Y = ksy*inv(K+lambda*eye(size(K)))*y;
%%
figure; hold on
plot(x,y,'bo');
plot(X_time,Y_time,'r','linewidth',2);
grid on
end
