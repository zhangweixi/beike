%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% …‰√≈≈–∂œ
% 2018-09-28
%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function shoot_result = Shoot(pass_data,compass_r,compass_l,compass_fs,court)
%% 
if isempty(pass_data)
    shoot_result = [];
    return;
end
[m,~] = size(court); [n,~] = size(pass_data); k = 1; shoot_result = [];
LAT = (court(m,1)+court(m,3))/2; LON = (court(m,2)+court(m,4))/2;
% Lat = fix(lat); LAT = Lat+(lat-Lat)*100/60;
% Lon = fix(lon); LON = Lon+(lon-Lon)*100/60;
% court1 = fix(court(m,1)/100); Court1 = court1+(court(m,1)/100-court1)*100/60;
% court2 = fix(court(m,2)/100); Court2 = court2+(court(m,2)/100-court2)*100/60;
% court3 = fix(court(m,3)/100); Court3 = court3+(court(m,3)/100-court3)*100/60;
% court4 = fix(court(m,4)/100); Court4 = court4+(court(m,4)/100-court4)*100/60;
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
    A = round((pass_data(i,3)-0.5)*compass_fs);
    B = round((pass_data(i,3)+0.5)*compass_fs);  
    if length(find(min(C)<compass_data(A:B,1)&compass_data(A:B,1)<max(C))) > 5
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