%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% GPS ���ݴ���
% 2018-09-04
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function [GPS_result,filterlat,filterlon] = GPS_handle(gps,fs)
%% ȥ������ת��GPS
gps = GPS_pretreatment(gps);
% �ж���û��gps
if isempty(gps)
    GPS_result = null; filterlat = null; filterlon = null;
    return;
end
filterlat = gps(:,1); filterlon = gps(:,2); 
%% �ٶ�v�����ٶ�a��Ծ��J
% setGlobalParam();
% time
Time = numel(filterlat)/fs;
% GPS distance
for i = 1:numel(filterlat)-1
    % ��һ�ַ���
%     Distance(i)= abs(GPSDist(filterlat(i), filterlon(i), filterlat(i+1), filterlon(i+1))); 
    % �ڶ��ַ���
    [distance,~] = GPS_calculate(filterlat(i),filterlon(i),filterlat(i+1),filterlon(i+1));
    Distance(i) = distance;   
end
Output_Distance = sum(Distance); % �ܾ���S
time = Time;                 % ��ʱ��
V = Output_Distance / time;  % ƽ���ٶ�V
% ˲ʱ�ٶȵ�һ�ַ���
% j = 1; k = 1;
% while j <= N
%     if j+fs-2<=N
%         Output_V(k) = sum(Distance(j:j+fs-2));
%         k = k+1; j = j+fs-1;
%     else
%         Output_V(k) = (fs-1)*sum(Distance(j:N))/(N-j+1);
%     end      
% end
% ˲ʱ�ٶȵڶ��ַ���
Output_V = diff(Distance,1) * fs; % �ٶ�
N = numel(Output_V);
Output_A = diff(Output_V,1) * fs; % ���ٶ�A
A = mean(abs(Output_A)); % ƽ�����ٶ�
%% ����ļ�
Output_T = 3/fs:1/fs:time-1/fs;
result1 = [V,Output_T]'; result2 = [A,Output_V(2:end)]'; result3 = [Output_Distance,Output_A]';
W = [0;filterlat(3:end-1)]; J = [0;filterlon(3:end-1)];
GPS_result = [result1,result2,result3,W,J];
end

