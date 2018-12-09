clc; clear all;
pathname = 'G:\1257';
sensor_R = 'sensor-R.txt'; sensor_L = 'sensor-L.txt'; gps_L = 'gps-L.txt';
angle_R = 'angle-R.txt'; angle_L = 'angle-L.txt'; court_config = 'court-config.txt';
% 添加路径
addpath(genpath(pathname)); 
% Sensor
sensor_r = importdata(sensor_R)/1000; sensor_l = importdata(sensor_L)/1000; 
sensor_r(:,4:5) = sensor_r(:,4:5)*1000; sensor_l(:,4:5) = sensor_l(:,4:5)*1000;
Compass_R = importdata(angle_R); Compass_L = importdata(angle_L); 
GPS = importdata(gps_L);
Court_config = importdata(court_config);
%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% R_J = Touch(sensor_r,25,1000,100,26); % 判断右脚触球
% 
% L_J = Touch(sensor_l,25,1000,100,26); % 判断右脚触球


% PASS = Total_ball(sensor_r,sensor_l,GPS(:,1),GPS(:,2),100);
%%%%%%%%%%%%%%%%%%%%%%%%%%%%
fs = 100; lat = 0; n_r = length(sensor_r);
for i = 1:n_r
    A(i) = sqrt(sensor_r(i,1)^2+sensor_r(i,2)^2+sensor_r(i,3)^2);
    SMA(i) = sqrt(sensor_r(i,2)^2+sensor_r(i,3)^2);
end
% [Psi,Output_Psi] = Teager(SMA_R,fs); % 能量算子
% [DI,Output_DI] = Spectrum_Weigthing(SMA_R',fs); % 频谱权重
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% singular = error_ellipse(sensor_r(:,2),sensor_r(:,3),0.95); % 第一次筛选
% singular = error_ellipse(Output_Psi',Output_DI(2:n_r-1)',0.99); % 第一次筛选
% singular = error_ellipse3(Psi',DI(1:n_r-2)',SMA_R(1:n_r-2)',0.99); % 第一次筛选
singular = error_ellipse3(sensor_r(:,1),sensor_r(:,2),sensor_r(:,3),0.999); % 第一次筛选
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
singular(:,5) = A(singular(:,1));
% figure; plot(A); hold on; plot(singular(:,1),singular(:,5),'*'); hold on

% singular(:,4) = SMA_R(singular(:,1));
% figure; plot(SMA_R); hold on; plot(singular(:,1),singular(:,4),'.r');
D = ones(n_r,1); X_Y = zeros(n_r,1);
D(singular(:,1)) = A(singular(:,1)); X_Y(singular(:,1)) = SMA(singular(:,1));
% 总幅值
i = 1; l = 1;
while (i <= n_r)
    if D(i)~= 1
        j = 0;
        while D(i)~= 1
            j = j+1; i = i+1;
        end
        Z(l,1:j) = D(i-j:i-1);
        l = l+1;
    end
    i = i+1;
end
% X-Y的幅值
i = 1; l = 1;
while (i <= n_r)
    if X_Y(i)~= 0
        j = 0;
        while X_Y(i)~= 0
            j = j+1; i = i+1;
        end
        F(l,1:j) = X_Y(i-j:i-1);
        l = l+1;
    end
    i = i+1;
end
figure; plot(D); hold on
output = vibrate(D,3,100,100); % 第二次筛选

% output = vibrate(D,20,500,100); % 第二次筛选
% plot(output(:,1),output(:,2),'or'); hold on
% 第三次选择
i = 1; k = 1;
while i <= length(output)
    if output(i,1)+25 <= n_r
        B = find(D(output(i,1)-25 : output(i,1)+25) ~= 1)+output(i,1)-26;
    else
        B = find(D(output(i,1)-25 : n_r) ~= 1)+output(i,1)-26;
    end
    M = mean(D(B));
    if length(B) < 4
        S = std(D(B)); 
    end
    if length(B) > 10
        S = 0;
    else
        switch length(B)
            case 4
               S = 1/2*std(D(B));
            case 5
               S = 1/4*std(D(B));
            case 6
               S = 1/8*std(D(B));     
            case 7
               S = 1/16*std(D(B));    
            case 8
               S = 1/32*std(D(B)); 
            case 9
               S = 1/64*std(D(B)); 
            case 10
               S = 1/128*std(D(B)); 
        end
    end
    if  output(i,2) > M+S
        Output(k,:) = output(i,:);
        k = k+1;
    end
    i = i+1;
end 
% 计算速度
V = []; V_xy = [];
for i = 1:length(Output)
    [row,column] = find(Z == Output(i,2));
%     V(i) = (sum(Z(row,1:column)) - column)/10; % 最高点数据
    row = max(row);
    V(i) = (sum(Z(row,:))-length(find(Z(row,:) ~= 0)))/10; % 全部数据
    V_xy(i) = sum(F(row,:))/10; % 全部数据
end
Output(:,3) = V; Output(:,4) = SMA(Output(:,1)); Output(:,5) = V_xy; 
% plot(Output(:,1),Output(:,2),'r*'); 
%% BP分类处理
% 归一化
Mapped_A = mapminmax(Output(:,2)',0,1); % 加速度归一化
Mapped_V = mapminmax(Output(:,3)',0,1); % 速度归一化
Index =  Mapped_A .* Mapped_V ; Output(:,6) = Index;
Output = Output(Output(:,5) > 0.3,:);

plot(Output(:,1),Output(:,2),'r.'); hold on
% longpass3 = Long_pass(Output,0.25,10,6,100,n_r);

% longpass3 = Long_pass(Output,0.003,4,3,100,n_r);

% plot(longpass3(:,1),longpass3(:,6),'*'); 
%%
% [OUT,n] = Touch(sensor_r,3,100,100,4); 
% longpass3 = Long_pass(OUT,0.25,10,6,100,n_r); % 判断长传
% shortpass3 = Long_pass(OUT,0.01,5,3,100,n_r); % 判断短传
% pass = BALL_Z(sensor_r,sensor_l,GPS);
% PASS = Total_ball(sensor_r,sensor_l,GPS);
%% 判断长短传球
% 按照幅值筛选第一次
longpass1 = Output(Output(:,6) >= 0.25,:);
plot(longpass1(:,1),longpass1(:,2),'ko'); hold on
% 按照时间间隔筛选第二次
[m,~] = size(longpass1); 
j = 1; interval = 10;
longpass2 = []; longpass2(j,:) = longpass1(1,:);
for i = 2:m
    if  longpass1(i,1) - longpass2(j,1) < interval * fs
        [~,z] = max([longpass1(i,2) longpass2(j,2)]);
        if z == 1
            longpass2(j,:) = longpass1(i,:);
        end
    else
        j = j+1;
        longpass2(j,:) = longpass1(i,:);
    end
end
plot(longpass2(:,1),longpass2(:,2),'ro'); hold on
xlabel('时间/s'); ylabel('速度/m/s');
% 按照间隔触球次数筛选第三次
[m,~] = size(longpass2); longpass3 = [];
i = 1; k = 1; Inter = interval * fs;
while i <= m
    if longpass2(i,1)-Inter < 0
        min_time = 0;
    else
        min_time = longpass2(i,1)-Inter;
    end
    if longpass2(i,1)+Inter > n_r
        max_time = n_r;
    else
        max_time = longpass2(i,1)+Inter;
    end
    number = 0;[f,~] = size(Output);
    for j = 1:f
        if (Output(j,1) > min_time)&&(Output(j,1) < max_time)
            number = number+1;
        end
    end
    if number < 6
        longpass3(k,:) = longpass2(i,:); k = k+1;
    end
    i = i+1;
end
plot(longpass3(:,1),longpass3(:,2),'r*'); hold on
%%%%%%%%%%%%%%%%%%%%%%%%%%%%
pass = Total_ball(sensor_r,sensor_l,GPS);
shoot_result = Shoot_Z(pass,Compass_R,Compass_L,40,Court_config);
%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% longpass3 = Long_pass(Output,0.25,10,6,100,n_r); % 判断长传
% plot(longpass3(:,1),longpass3(:,2),'r*-'); 

% shortpass3 = Long_pass(Output,0.01,5,3,100,n_r); % 判断短传
% plot(shortpass3(:,1),shortpass3(:,2),'r*'); 
figure
plot(Court_config(1:1000,1),Court_config(1:1000,2),'k.'); hold on
for i = 1:1000 
    if Court_config(i,3) == 1
        plot(Court_config(i,1),Court_config(i,2),'w.'); hold on  % 射门区域
    end
    if Court_config(i,4) == 1
        plot(Court_config(i,1),Court_config(i,2),'r.'); hold on  % 禁区
    end
end
[m,~] = size(pass); chu = 0; chang = 0; duan = 0;
for j = 1:m
    if pass(j,2) == 1
        plot(pass(j,5),pass(j,6),'r*','markersize',12); hold on % 长传  
        chang = chang+1;
    end
    if pass(j,2) == 2
        plot(pass(j,5),pass(j,6),'kp','markersize',10); hold on % 短传
        duan = duan+1;
    end
    if pass(j,2) == 3
        plot(pass(j,5),pass(j,6),'b*','markersize',5); hold on % 触球
        chu = chu+1;
    end    
end
plot(shoot_result(:,1),shoot_result(:,2),'rh','markersize',15); hold on % 射门
% 球门
plot(Court_config(1001,1),Court_config(1001,2),'b<','markersize',15); hold on 
plot(Court_config(1001,3),Court_config(1001,4),'b<','markersize',15); hold on 
plot(Court_config(1002,1),Court_config(1002,2),'b>','markersize',15); hold on 
plot(Court_config(1002,3),Court_config(1002,4),'b>','markersize',15); axis equal


