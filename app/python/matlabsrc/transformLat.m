function ret = transformLat(x, y)
    global PI  ;               
    ret = -100.0 + 2.0*x + 3.0*y + 0.2*y^2 + 0.1*x*y + 0.2*abs(x)^0.5;
    ret = ret + (20.0 * sin(6.0 * x * PI) + 20.0 * sin(2.0 * x * PI)) * 2.0 / 3.0;
    ret = ret + (20.0 * sin(y * PI) + 40.0 * sin(y / 3.0 * PI)) * 2.0 / 3.0;
    ret = ret + (160.0 * sin(y / 12.0 * PI) + 320 * sin(y * PI / 30.0)) * 2.0 / 3.0;
end