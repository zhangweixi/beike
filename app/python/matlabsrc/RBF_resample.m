%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 重采样
% 2018-09-05
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%% 插值法
function output = RBF_resample(input,fs,M)
% 时间序列
flag = length(input)/fs;
time = 1/fs:1/fs:flag;
Time = 1/M:1/M:flag;
% output = interpft(input,length(Time));    % 基于FFT快速傅里叶变换的插值法
output = interp1(time,input,Time,'spline'); % 基于多项式的插值法
%% figure
% plot(time,Re_input,'b'); hold on
% plot(Time,output,'r'); legend('original','spline');
% figure
% plot(Re_input(:,1),Re_input(:,2),'.b'); hold on
% plot(output(:,1),output(:,2),'.r'); legend('original','spline');
end