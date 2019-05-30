%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% GPS插值算法
% 2018-07-25
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function [Re_sample_lat,Re_sample_lon,Time] = GPS_inter(lat,lon,fs,M)
m = max(size(lat));
time = m/fs;
k = 1;
if M>fs
  N = fix((M*time-m)/(m-1))+2;
  i = 1;
  while i < m
       inter = 1;
       while (outOfChina(lat(i+1), lon(i+1)))
          i = i+1;
          inter = inter+1;
       end
       re_sample_lat = linspace(lat(i+1-inter),lat(i+1),(N+1)*inter-1);
       re_sample_lon = linspace(lon(i+1-inter),lon(i+1),(N+1)*inter-1);
       for j = 1:numel(re_sample_lat)-1
           Re_sample_lat(k) = re_sample_lat(j);
           Re_sample_lon(k) = re_sample_lon(j);
           k = k+1;
       end  
       i = i+1;
  end
else
    G = fix((m-M*time)/(M*time-1));
    flag = 1;
    while flag<m
        Re_sample_lat(k) = lat(flag);
        Re_sample_lon(k) = lon(flag);
        k = k+1;
        flag = flag+G+1;
        if flag<m
            while (outOfChina(lat(flag), lon(flag)))
            flag = flag+1;
            end
        end
    end
end
Time = linspace(0,time,length(Re_sample_lat))';
figure; 
% hold on
% plot(lat,lon,'o');
plot(Re_sample_lat,Re_sample_lon,'r*');
title('重采样算法');
xlabel('lat'); ylabel('lon');
% legend('原始数据','重采样数据');
end

    
         
         