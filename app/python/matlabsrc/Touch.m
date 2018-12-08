% clc; clear all;
% pathname = 'G:\data';
% sensor_R = 'sensor-R.txt'; sensor_L = 'sensor-L.txt'; gps_L = 'gps-L.txt';
% angle_R = 'angle-R.txt'; angle_L = 'angle-L.txt'; 
% % ���·��
% addpath(genpath(pathname)); 
% % Sensor
% sensor = importdata(sensor_R)/1000;
% lamda = 3; sigma1 = 100; sigma2 = 100; sigma3 = 4;
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function [Output,m] = Touch(sensor,lamda,sigma1,sigma2,sigma3)
m = length(sensor);
for i = 1:m
    A(i) = sqrt(sensor(i,1)^2+sensor(i,2)^2+sensor(i,3)^2); % ���ٶ�
    SMA(i) = sqrt(sensor(i,2)^2+sensor(i,3)^2); % X_Y���ٶ�
end
singular = error_ellipse3(sensor(:,1),sensor(:,2),sensor(:,3),0.999); % ��һ��ɸѡ
if isempty(singular)
    Output = [];
    return;
end
singular(:,5) = A(singular(:,1)); D = ones(m,1); X_Y = zeros(m,1);
D(singular(:,1)) = A(singular(:,1)); X_Y(singular(:,1)) = SMA(singular(:,1));
% �ܷ�ֵ
i = 1; l = 1;
while (i <= m)
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
% X-Y�ķ�ֵ
i = 1; l = 1;
while (i <= m)
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
output = vibrate(D,lamda,sigma1,sigma2);  % �ڶ���ɸѡ
% �ж���û������
if isempty(output)
    Output = [];
    return;
end
% ������ѡ��
i = 1; k = 1; Flag = sigma2/4; [U,~] = size(output);
while i <= U
    if output(i,1)+Flag <= m
        B = find(D(output(i,1)-Flag : output(i,1)+Flag)~=1)+output(i,1)-Flag-1;
    else
        B = find(D(output(i,1)-Flag : m) ~= 1)+output(i,1)-Flag-1;
    end
    M = mean(D(B));
    if length(B) < sigma3
        S = std(D(B)); 
    end
    if length(B) > sigma3+6
        S = 0;
    else
        switch length(B)
            case sigma3
               S = 1/2*std(D(B));
            case sigma3+1
               S = 1/4*std(D(B));
            case sigma3+2
               S = 1/8*std(D(B));     
            case sigma3+3
               S = 1/16*std(D(B));    
            case sigma3+4
               S = 1/32*std(D(B));  
            case sigma3+5
               S = 1/64*std(D(B));  
            case sigma3+6
               S = 1/128*std(D(B)); 
        end
    end
    if  output(i,2) > M+S
        Output(k,:) = output(i,:);
        k = k+1;
    end
    i = i+1;
end
% �����ٶ�
[E,~] = size(Output); V = []; V_xy = [];
for i = 1:E
    [row,~] = find(Z == Output(i,2));
%     V(i) = (sum(Z(row,1:column)) - column)/10; % ��ߵ�����
    row = max(row);
    V(i) = (sum(Z(row,:))-length(find(Z(row,:) ~= 0)))/10; % ȫ������
    V_xy(i) = (sum(F(row,:)))/10; % ȫ������
end
Output(:,3) = V; Output(:,4) = SMA(Output(:,1)); Output(:,5) = V_xy; 
% ��һ��
Mapped_A = mapminmax(Output(:,2)',0,1); % ���ٶȹ�һ��
Mapped_V = mapminmax(Output(:,3)',0,1); % �ٶȹ�һ��
Index =  Mapped_A .* Mapped_V ; Output(:,6) = Index;
Output = Output(Output(:,5) > 0.3,:);
end