%%%%%%%%%%%%%%%%%%%%
% ’“Œª÷√
% 2018-12-07
%%%%%%%%%%%%%%%%%%%%
function [time,lat,lon] = Location(sensor,gps)
[m,n] = size(gps); k = 1; GPS = [];
for i = 1:m
    if gps(i,4) == sensor(5)
        GPS(k,1:n) = gps(i,:); GPS(k,n+1) = abs(sensor(4) - gps(i,3));
        k =k+1;
    end
end
[~,z] = min(GPS(:,n+1));
time = GPS(z,3); lat = GPS(z,1); lon = GPS(z,2);
end