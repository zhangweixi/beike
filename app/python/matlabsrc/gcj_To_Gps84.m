%/**
% * * 火星坐标系 (GCJ-02) to 84 * * @param lon * @param lat * @return
% * */
function Gps = gcj_To_Gps84(lat, lon) 
    gps = transform(lat, lon);
    lontitude = lon * 2 - gps.Lon;
    latitude = lat * 2 - gps.Lat;
    Gps = setGps(latitude, lontitude);
end
