function Gps = transform(lat, lon) 
    global PI  ;               
    global Param_a   ;       
    global Param_ee   ; 
    if outOfChina(lat, lon) 
        Gps = setGps(lat, lon);
    else
        dLat = transformLat(lon - 105.0, lat - 35.0);
        dLon = transformLon(lon - 105.0, lat - 35.0);
        radLat = lat / 180.0 * PI;
        magic = sin(radLat);
        magic = 1 - Param_ee * magic * magic;
        sqrtMagic = magic^0.5;
        dLat = (dLat * 180.0) / ((Param_a * (1 - Param_ee)) / (magic * sqrtMagic) * PI);
        dLon = (dLon * 180.0) / (Param_a / sqrtMagic * cos(radLat) * PI);
        mgLat = lat + dLat;
        mgLon = lon + dLon;
        Gps = setGps(mgLat, mgLon);
    end
end