function flag =  outOfChina(lat, lon)
    if (lon < 72.004 || lon > 137.8347)
        flag = true;
    elseif (lat < 0.8293 || lat > 55.8271)
        flag = true;
    else
        flag = false;
    end
end