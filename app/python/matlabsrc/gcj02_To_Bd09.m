%%%%%%%%%%%%%%%%%%%%%%%%%%%%5
% 2018-07-31
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function Gps = gcj02_To_Bd09(gg_lat, gg_lon)
    global PI  ;                
    x = gg_lon; 
    y = gg_lat;
    z = (x * x + y * y)^0.5 + 0.00002 * sin(y * PI);
    theta = atan2(y, x) + 0.000003 * cos(x * PI);
    bd_lon = z * cos(theta) + 0.0065;
    bd_lat = z * sin(theta) + 0.006;
    Gps = setGps(bd_lat, bd_lon);
end