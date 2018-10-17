%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% �ز���
% 2018-09-05
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%% ��ֵ��
function output = RBF_resample(input,fs,M)
flag = length(input)/fs;
time = 1/fs:1/fs:flag;
Time = 1/M:1/M:flag;
if fs>M
    output = resample(input,M,fs);
else
    % ����FFT���ٸ���Ҷ�任�Ĳ�ֵ��
%     output = interpft(input,length(Time));
    % ���ڶ���ʽ�Ĳ�ֵ��
    output = interp1(time,input,Time,'spline');
end
% figure
% plot(time,input,'b'); hold on
% plot(Time,output,'r'); legend('original','spline');
end
