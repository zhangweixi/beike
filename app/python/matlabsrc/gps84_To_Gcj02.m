%%
% * 84 to 鐏槦鍧愭爣绯?(GCJ-02) World Geodetic System ==> Mars Geodetic System
% * 
% * @param lat
% * @param lon
% * @return
% */
function [Gps,flag] = gps84_To_Gcj02(lat, lon, time)    
    global PI  ;               
    global Param_a   ;       
    global Param_ee   ; 
    flag = 1;
    if (outOfChina(lat, lon)) 
        Gps = setGps(0, 0);
        flag = 0;
        fprintf('该点无效出了中国区域 :');
        fprintf('Lat %.9f :Lon %.9f :时间 %.9f\n\n',lat,lon,time);
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
