%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% GPS 数据处理
% 2018-09-04
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function [GPS_result,filterlat,filterlon] = GPS_handle(gps,fs)
%% 去除误差点转换GPS
gps = GPS_pretreatment(gps);
% 判断有没有gps
if isempty(gps)
    GPS_result = null; filterlat = null; filterlon = null;
    return;
end
filterlat = gps(:,1); filterlon = gps(:,2); 
%% 速度v、加速度a、跃度J
% setGlobalParam();
% time
Time = numel(filterlat)/fs;
% GPS distance
for i = 1:numel(filterlat)-1
    % 第一种方法
%     Distance(i)= abs(GPSDist(filterlat(i), filterlon(i), filterlat(i+1), filterlon(i+1))); 
    % 第二种方法
    [distance,~] = GPS_calculate(filterlat(i),filterlon(i),filterlat(i+1),filterlon(i+1));
    Distance(i) = distance;   
end
Output_Distance = sum(Distance); % 总距离S
time = Time;                 % 总时间
V = Output_Distance / time;  % 平均速度V
% 瞬时速度第一种方案
% j = 1; k = 1;
% while j <= N
%     if j+fs-2<=N
%         Output_V(k) = sum(Distance(j:j+fs-2));
%         k = k+1; j = j+fs-1;
%     else
%         Output_V(k) = (fs-1)*sum(Distance(j:N))/(N-j+1);
%     end      
% end
% 瞬时速度第二种方案
Output_V = diff(Distance,1) * fs; % 速度
N = numel(Output_V);
Output_A = diff(Output_V,1) * fs; % 加速度A
A = mean(abs(Output_A)); % 平均加速度
%% 结果文件
Output_T = 3/fs:1/fs:time-1/fs;
result1 = [V,Output_T]'; result2 = [A,Output_V(2:end)]'; result3 = [Output_Distance,Output_A]';
W = [0;filterlat(3:end-1)]; J = [0;filterlon(3:end-1)];
GPS_result = [result1,result2,result3,W,J];
end

