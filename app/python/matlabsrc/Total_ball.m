%%%%%%%%%%%%%%%%%%%%%%%%%%
% ���ҽŴ������ݶԱ�
% 2018-11-20
%%%%%%%%%%%%%%%%%%%%%%%%%%
function PASS = Total_ball(Sensor_R,Sensor_L,gps)
% ���ҽŵĴ�������
% pass = BALL(Sensor_R,Sensor_L,filterlat,filterlon,sensor_fs); BALL_Z(sensor_r,sensor_l,gps)
pass = BALL_Z(Sensor_R,Sensor_L,gps);
% �ж���û������
if isempty(pass) 
    PASS = [];
    return;
end
% ����ʱ��������
Total = sortrows(pass,[3 4]); % PASS = Total ;
[m,~] = size(Total); j = 1; PASS = []; PASS(j,:) = Total(1,:);
for i = 2:m
    switch  PASS(j,2)
        case 3 % �����ж�
            if Total(i,3) - PASS(j,3) < 1400
                [~,z] = max([Total(i,7),PASS(j,7)]);
                if z == 1
                    PASS(j,:) = Total(i,:);
                end
            else
                j = j+1;
                PASS(j,:) = Total(i,:);
            end
        case 2   % �̴��ж�
            if Total(i,3) - PASS(j,3) < 5000
                [~,z] = max([Total(i,7),PASS(j,7)]);
                if z == 1
                    PASS(j,:) = Total(i,:);
                end
           else
                j = j+1;
                PASS(j,:) = Total(i,:);
            end
        case 1    % �����ж�
            if Total(i,3) - PASS(j,3) < 10000
                [~,z] = max([Total(i,7),PASS(j,7)]);
                if z == 1
                    PASS(j,:) = Total(i,:);
                end
            else
                j = j+1;
                PASS(j,:) = Total(i,:);
            end 
    end
end
end