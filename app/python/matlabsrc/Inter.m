%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 插值算法
% 2018-07-25
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function [Re_sample,Time] = Inter(x,fs,M)
m = max(size(x));
time = m/fs;
k = 1;
if M>fs
    N = fix((M*time-m)/(m-1))+2;
    for i = 1:m-1
        re_sample = linspace(x(i),x(i+1),N);
        for j = 1:N-1
            Re_sample(k) = re_sample(j);
            k = k+1;
        end        
    end
else
    G = fix((m-M*time)/(M*time-1));
    flag = 1;
    while flag<m
        Re_sample(k) = x(flag);
        k = k+1;
        flag = flag+G+1;
    end
end
Time = linspace(0,time,length(Re_sample))';
% figure; hold on
% plot(linspace(0,time,m)',x,'o');
% plot(Time,Re_sample,'r*');
% title('重采样算法');
% xlabel('时间/s'); ylabel('幅值');
% legend('原始数据','重采样数据');
end

    
         
         