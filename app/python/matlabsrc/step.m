%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 计算步数
% 2018-07-31
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function [Time,Amplitude,number] = step(data,fs,type)
[n,m] = size(data);
Time = n/fs; 
for i = 1:m
    M(i) = mean(data(:,i));
    S(i) = std(data(:,i));    
end
switch type
    case 'model'
        for i = 1:n
            SMA(i) = (data(i,1)^4+data(i,2)^4+data(i,3)^4)^1/4;
        end
    case 'norm'
        for i = 1:n
            SMA(i) = (data(i,1)^2+data(i,2)^2+data(i,3)^2)^1/2;
        end
    case 'cube'
        for i = 1:n
            SMA(i) = (data(i,1)^3+data(i,2)^3+data(i,3)^3)^1/3;
        end
end
% [output , ~] = StandardKalmanFilter(SMA,100,100); % 卡尔曼滤波
% [result] = Baophasefilter(output,fs); % 低通滤波
[Psi,Output_Psi] = Teager(SMA,fs);
% figure
% t = linspace(0,Time,numel(Output_Psi))';
%% 自适应门限法
N = 60;
M = fix(numel(Output_Psi)/N);
R = Output_Psi.^3;
flag = 0;
for i = 1:M
    P = sum(R(N*(i-1)+1:N*i))/N;
    C = 1-0.0001^(1/(N-1));
    V = C*P;
    [M,I] = max(Output_Psi(N*(i-1)+1:N*i));
    if M>V  
        flag = flag+1;
        time(flag) = I/fs;
        Amplitude(flag) = M;
    end
end
% Time = time;
% while (min(diff(Time))<0.2)
%     Flag = 1;
%     for i = 2:length(time)
%         mid_t = time(i)-time(i-1);
%         if mid_t<0.2
%             if Amplitude(i)>Amplitude(i-1)
%                 Time(Flag) = time(i);
%                 Amplitude(Flag) = Amplitude(i);
%                 Flag = Flag+1;
%             end
%         else
%             Time(Flag) = time(i-1);
%             Amplitude(Flag) = Amplitude(i-1);
%             Flag = Flag+1;
%         end 
%     end
% end
number = length(Time);
end