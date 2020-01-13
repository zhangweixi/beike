%%%%%%%%%%%%%%%%%%%%%%
% 区分射门和传球
% 2018-11-15
%%%%%%%%%%%%%%%%%%%%%%
function Pass = DIV(passdata,shootdata)
if isempty(passdata)
    Pass = [];
    return;
end
if isempty(shootdata)
    Pass = passdata;
    return;
end
%% 去掉传球中的射门
[m,~] = size(passdata); Pass = []; k = 1; i = 1;
while i <= m
    if sum(shootdata(:,1) ~= passdata(i,5)) == length(shootdata(:,1))
        Pass(k,:) = passdata(i,:);
        k = k+1;
    end
    i = i+1;
end
end