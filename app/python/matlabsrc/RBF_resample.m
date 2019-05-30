%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% �ز���
% 2018-09-05
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
%% ��ֵ��
function output = RBF_resample(input,fs,M)
% ʱ������
flag = length(input)/fs;
time = 1/fs:1/fs:flag;
Time = 1/M:1/M:flag;
% output = interpft(input,length(Time));    % ����FFT���ٸ���Ҷ�任�Ĳ�ֵ��
output = interp1(time,input,Time,'spline'); % ���ڶ���ʽ�Ĳ�ֵ��
%% figure
% plot(time,Re_input,'b'); hold on
% plot(Time,output,'r'); legend('original','spline');
% figure
% plot(Re_input(:,1),Re_input(:,2),'.b'); hold on
% plot(output(:,1),output(:,2),'.r'); legend('original','spline');
end