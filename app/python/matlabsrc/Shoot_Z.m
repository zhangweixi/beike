%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 射门判断
% 2018-09-28
%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function shoot_result = Shoot_Z(pass_data,compass_r,compass_l,compass_fs,court)
% 判断有没有数据 
if isempty(pass_data)
    shoot_result = [];
    return;
end
[m,~] = size(court); [n,~] = size(pass_data); DIS = [];
% 判断每一次触球所在的球场区域
for i = 1:n
    for j = 1:m-2
        [distance,~] = GPS_calculate(pass_data(i,5),pass_data(i,6),court(j,1),court(j,2));
        DIS(j) = distance;
    end
    [~,location] = min(DIS); pass_data(i,8) = location;
end
% 判断射门
k = 1; shoot_result = [];
for i = 1:n
    if pass_data(i,1) == 1
        compass_data = compass_r;
    else
        compass_data = compass_l;
    end
    if court(pass_data(i,8),4) == 1 % 近射进球
        if pass_data(i,2) == 1 % 长传
            [LAT,LON,Distance,Angle,~] = Goal(pass_data(i,5),pass_data(i,6),court);
            shoot_result(k,:) = [pass_data(i,5),pass_data(i,6),LAT,LON,pass_data(i,7),pass_data(i,4),Angle,Distance];
            k = k+1;
        end
        if pass_data(i,2) == 2 % 短传
            % 判断角度
            [LAT,LON,Distance,Angle,C] = Goal(pass_data(i,5),pass_data(i,6),court);
            A = round((pass_data(i,3)-0.5)*compass_fs);
            B = round((pass_data(i,3)+0.5)*compass_fs); 
            if length(find(min(C)<compass_data(A:B,1)&compass_data(A:B,1)<max(C))) >= 1
                shoot_result(k,:) = [pass_data(i,5),pass_data(i,6),LAT,LON,pass_data(i,7),pass_data(i,4),Angle,Distance];
                k = k+1;
            end
        end
    end
    if court(pass_data(i,8),3) == 1 % 远射进球
        if pass_data(i,2) == 1 % 长传
            % 判断角度
            [LAT,LON,Distance,Angle,C] = Goal(pass_data(i,5),pass_data(i,6),court);
            A = round((pass_data(i,3)-0.5)*compass_fs);
            B = round((pass_data(i,3)+0.5)*compass_fs); 
            if length(find(min(C)<compass_data(A:B,1)&compass_data(A:B,1)<max(C))) >= 1
                shoot_result(k,:) = [pass_data(i,5),pass_data(i,6),LAT,LON,pass_data(i,7),pass_data(i,4),Angle,Distance];
                k = k+1;
            end
        end
    end
end
% LAT = (court(m,1)+court(m,3))/2; LON = (court(m,2)+court(m,4))/2;
% Court1 = court(m,1); Court2 = court(m,2); Court3 = court(m,3); Court4 = court(m,4);
% for i = 1:n
%     [distance1,azimuth1] = GPS_calculate(pass_data(i,5),pass_data(i,6),Court1,Court2);
%     [distance2,azimuth2] = GPS_calculate(pass_data(i,5),pass_data(i,6),Court3,Court4);
%     C = [azimuth1,azimuth2];
%     if pass_data(i,1) == 1
%         compass_data = compass_r;
%     else
%         compass_data = compass_l;
%     end
%     A = round((pass_data(i,3)-0.1)*compass_fs);
%     B = round((pass_data(i,3)+0.1)*compass_fs);  
%     if length(find(min(C)<compass_data(A:B,1)&compass_data(A:B,1)<max(C))) > 4
%         distance = (distance1+distance2)/2;
%         angle = mean(compass_data(A:B,1));
%         shoot_result(k,:) = [pass_data(i,5),pass_data(i,6),LAT,LON,pass_data(i,7),pass_data(i,4),angle,distance];
%         k = k+1;
%     end
% end
if isempty(shoot_result)
    shoot_result = [];
    return;
end
end