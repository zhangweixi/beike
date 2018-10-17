%%%%%%%%%%%%%%%%%%%%%%%%%
% 判断触球状态
% 2018-09-05
%%%%%%%%%%%%%%%%%%%%%%%%%
function pass = BALL(sensor_r,sensor_l,lat,lon,fs,M)
% 数据类型：第一列代表左右脚1-右脚、0-左脚
% 第二列代表有球状态：1-长传、2-短传、3-触球。
%% RIGHT
n_r = length(sensor_r);
m = length(lat);
GPS_R = RBF_resample([lat,lon],fs,ceil(fs*n_r/m));
for i = 1:n_r
    SMA_R(i) = sqrt((sensor_r(i,2)^2+sensor_r(i,3)^2));
end
if max(SMA_R)>5
   k = 1;kk = 1;kkk = 1;flag = 1;
   for i = 1:n_r
       SMA_R(i) = sqrt((sensor_r(i,2)^2+sensor_r(i,3)^2));
       % 长传1
       if SMA_R(i)>=21
          pass_r1(k,1) = 1;pass_r1(k,2) = 1;pass_r1(k,3) = i/M;pass_r1(k,4) = SMA_R(i);
          pass_r1(k,5) = GPS_R(i,1);pass_r1(k,6) = GPS_R(i,2);pass(flag,:) = pass_r1(k,:);
          k = k+1;flag = flag+1;
       end
       % 短传2
       if SMA_R(i)<21 && SMA_R(i)>=10
          pass_r2(kk,1) = 1;pass_r2(kk,2) = 2;pass_r2(kk,3) = i/M;pass_r2(kk,4) = SMA_R(i);
          pass_r2(kk,5) = GPS_R(i,1);pass_r2(kk,6) = GPS_R(i,2);pass(flag,:) = pass_r2(k,:);
          kk = kk+1;flag = flag+1;
       end
       % 触球3
       if SMA_R(i)<10 && SMA_R(i)>=5
          pass_r3(kkk,1) = 1;pass_r3(kkk,2) = 3;pass_r3(kkk,3) = i/M;pass_r3(kkk,4) = SMA_R(i);
          pass_r3(kkk,5) = GPS_R(i,1);pass_r3(kkk,6) = GPS_R(i,2);pass(flag,:) = pass_r3(k,:);  
          kkk = kkk+1;flag = flag+1;
       end
   end
else
    pass = 0;
end
%% LEFT
n_l = length(sensor_l);
GPS_L = RBF_resample([lat,lon],fs,ceil(fs*n_l/m));
for i = 1:n_l
    SMA_L(i) = sqrt((sensor_l(i,2)^2+sensor_l(i,3)^2));
end
if max(SMA_L)>5
   k = 1;kk = 1;kkk = 1;
   for i = 1:n_l
       % 长传1
       if SMA_L(i)>=21
          pass_l1(k,1) = 0;pass_l1(k,2) = 1;pass_l1(k,3) = i/M;pass_l1(k,4) = SMA_L(i);
          pass_l1(k,5) = GPS_L(i,1);pass_l1(k,6) = GPS_L(i,2);pass(flag,:) = pass_l1(k,:); 
          k = k+1;flag = flag+1;
       end
       % 短传2
       if SMA_L(i)<21 && SMA_L(i)>=10
          pass_l2(kk,1) = 0;pass_l2(kk,2) = 2;pass_l2(kk,3) = i/M;pass_l2(kk,4) = SMA_L(i);
          pass_l2(kk,5) = GPS_L(i,1);pass_l2(kk,6) = GPS_L(i,2);pass(flag,:) = pass_l2(k,:); 
          kk = kk+1;flag = flag+1;
       end
       % 触球3
       if SMA_L(i)<10 && SMA_L(i)>=5
          pass_l3(kkk,1) = 0;pass_l3(kkk,2) = 3;pass_l3(kkk,3) = i/M;pass_l3(kkk,4) = SMA_L(i);
          pass_l3(kkk,5) = GPS_L(i,1);pass_l3(kkk,6) = GPS_L(i,2);pass(flag,:) = pass_l3(k,:);    
          kkk = kkk+1;flag = flag+1;
       end
   end
else
    pass = 0;
end
end


