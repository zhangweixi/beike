%%%%%%%%%%%%%%%%%%%%%%%%%%
% Teager��������
% 2018-08-15
%%%%%%%%%%%%%%%%%%%%%%%%%%
function [Psi,Output_Psi] = Teager(data,fs)
% [output , ~] = StandardKalmanFilter(data,100,100); % �������˲�
% [data] = Baophasefilter(data,fs); % ��ͨ�˲�
m = length(data);
D = power(diff(data),2);
n = length(D);
Psi = D(2:n) - data(2:m-1).* diff(data,2);
Output_Psi = mapminmax(Psi);
Time = 0:1/fs:(length(Psi)-1)/fs;
% figure
% plot(Time,Psi);
% xlabel('ʱ��/s'); ylabel('Teager����');
end