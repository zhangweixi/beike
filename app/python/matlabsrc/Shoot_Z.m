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
[m,~] = size(court); [n,~] = size(pass_data); k = 1; shoot_result = [];
% 判断每一次触球所在的球场区域






LAT = (court(m,1)+court(m,3))/2; LON = (court(m,2)+court(m,4))/2;
Court1 = court(m,1); Court2 = court(m,2); Court3 = court(m,3); Court4 = court(m,4);
for i = 1:n
    [distance1,azimuth1] = GPS_calculate(pass_data(i,5),pass_data(i,6),Court1,Court2);
    [distance2,azimuth2] = GPS_calculate(pass_data(i,5),pass_data(i,6),Court3,Court4);
    C = [azimuth1,azimuth2];
    if pass_data(i,1) == 1
        compass_data = compass_r;
    else
        compass_data = compass_l;
    end
    A = round((pass_data(i,3)-0.1)*compass_fs);
    B = round((pass_data(i,3)+0.1)*compass_fs);  
    if length(find(min(C)<compass_data(A:B,1)&compass_data(A:B,1)<max(C))) > 4
        distance = (distance1+distance2)/2;
        angle = mean(compass_data(A:B,1));
        shoot_result(k,:) = [pass_data(i,5),pass_data(i,6),LAT,LON,pass_data(i,7),pass_data(i,4),angle,distance];
        k = k+1;
    end
end
if isempty(shoot_result)
    shoot_result = [];
    return;
end
end