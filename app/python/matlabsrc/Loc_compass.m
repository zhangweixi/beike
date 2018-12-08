%%%%%%%%%%%%%%%%%%%%
% ’“Œª÷√
% 2018-12-07
%%%%%%%%%%%%%%%%%%%%
function [A,B] = Loc_compass(pass,compass,compass_fs)
[m,~] = size(compass); Compass = [];
for i = 1:m
    Compass(i) = abs(pass(3) - compass(i,4));
end
[~,T] = min(Compass);
A = T-compass_fs/2; B = T+compass_fs/2;
end


