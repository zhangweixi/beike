function dist = GPSDist(lat1, lon1, lat2, lon2)  
    global PI;  
    if(~PI)
        PI = 3.1415926535897932384626;
    end
    EARTH_RADIUS = 6378.137;

    a = lat1*pi/180  - lat2*pi/180 ;

    b = lon1*pi/180  - lon2*pi/180 ;

    dist = EARTH_RADIUS.*2.*asin(sqrt(sin(a./2).^2 + cos(lat1.*PI./180).*cos(lat2.*PI./180).*sin(b./2).^2));
    
    dist = dist * 1000;
end