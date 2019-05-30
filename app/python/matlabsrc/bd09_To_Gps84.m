%%
% * (BD-09)-->84
% * @param bd_lat   @param bd_lon
function Gps = bd09_To_Gps84(bd_lat, bd_lon) 
    Gps = bd09_To_Gcj02(bd_lat, bd_lon);
    Gps = gcj_To_Gps84(Gps.Lat,Gps.Lon);
end