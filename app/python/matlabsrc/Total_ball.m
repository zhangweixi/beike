%%%%%%%%%%%%%%%%%%%%%%%%%%
% 左右脚传球数据对比
% 2018-11-20
%%%%%%%%%%%%%%%%%%%%%%%%%%
function PASS = Total_ball(Sensor_R,Sensor_L,filterlat,filterlon,sensor_fs)
% 左右脚的触球数据
% pass = BALL(Sensor_R,Sensor_L,filterlat,filterlon,sensor_fs);
pass = BALL_Z(Sensor_R,Sensor_L,filterlat,filterlon,sensor_fs);
% 判断有没有数据
if isempty(pass) 
    PASS = [];
    return;
end
% 按照时间间隔排序
Total = sortrows(pass,[3 4]); 
[m,~] = size(Total); j = 1; PASS = []; PASS(j,:) = Total(1,:);
for i = 2:m
    if PASS(j,2) == 3 % 触球判断
            if Total(i,3) - PASS(j,3) < 1
                [~,z] = max([Total(i,4),PASS(j,4)]);
                if z == 1
                    PASS(j,:) = Total(i,:);
                end
            else
                j = j+1;
                PASS(j,:) = Total(i,:);
            end
    end
    if PASS(j,2) == 2   % 短传判断
            if Total(i,3) - PASS(j,3) < 4
                [~,z] = max([Total(i,4),PASS(j,4)]);
                if z == 1
                    PASS(j,:) = Total(i,:);
                end
           else
                j = j+1;
                PASS(j,:) = Total(i,:);
            end
    end
    if PASS(j,2) == 1    % 长传判断
            if Total(i,3) - PASS(j,3) < 10
                [~,z] = max([Total(i,4),PASS(j,4)]);
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