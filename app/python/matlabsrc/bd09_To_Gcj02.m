%%/**
% * * 火星坐标系 (GCJ-02) 与百度坐标系 (BD-09) 的转换算法 * * 将 BD-09 坐标转换成GCJ-02 坐标 * * @param
% * bd_lat * @param bd_lon * @return
% */
function Gps = bd09_To_Gcj02(bd_lat, bd_lon) 
    global PI  ;               
    x = bd_lon - 0.0065;
    y = bd_lat - 0.006;
    z = (x * x + y * y)^0.5 - 0.00002 * sin(y * PI);
    theta = atan2(y, x) - 0.000003 * cos(x * PI);
    gg_lon = z * cos(theta);
    gg_lat = z * sin(theta);
    Gps = setGps(gg_lat, gg_lon);
end
