%%%%%%%%%%%%%%%%%%%%%%%%%
% 2019-01-06
% 纠正触球次数
%%%%%%%%%%%%%%%%%%%%%%%%%
function output = validation_touch(PASS,interval)
output = [];
% 判断有没有数据
if isempty(PASS)
    return;
end
[m,~] = size(PASS); 
if m <= interval
    output = PASS; return;
else
    space = 1:interval:m;
end
n = length(space);
if space(n) ~= m
    space(n+1) = m;
end
j = 1; k = 1;
while j <= n
    if sum(PASS(space(j):space(j+1)-1,2) == 3) < interval
        num = numel(PASS(space(j):space(j+1)-1,2));
        output(k:k+num-1,:) = PASS(space(j):space(j+1)-1,:);
        k = k+num;
    end
    j = j+1;
end
output(end+1,:) = PASS(end,:);
end