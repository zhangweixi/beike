%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 重采样
% 2018-09-05
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%% 插值法
function output = RBF_resample(input,fs,M)
flag = length(input)/fs;
time = 1/fs:1/fs:flag;
Time = 1/M:1/M:flag;
if fs>M
    output = resample(input,M,fs);
else
    % 基于FFT快速傅里叶变换的插值法
%     output = interpft(input,length(Time));
    % 基于多项式的插值法
    output = interp1(time,input,Time,'spline');
end
% figure
% plot(time,input,'b'); hold on
% plot(Time,output,'r'); legend('original','spline');
end
