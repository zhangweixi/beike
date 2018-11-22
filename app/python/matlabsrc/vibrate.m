%%%%%%%%%%%%%%%%%%%%%%%%%%
% 2018-10-31
% 判断震荡程度
%%%%%%%%%%%%%%%%%%%%%%%%%%%
function output = vibrate(data,lamda,sigma1,sigma2)
n = length(data);
if (nargin < 4)
    i = 1; % 判断动荡程度（第一种）
    while i <= n
        if data(i)~= 1
           j = 0;
           while data(i)~= 1
               j = j+1; i = i+1;
           end
           if j < lamda
              data(i-j:i-1) = 1;
           end
        end
        i = i+1;
    end 
else  
    i = 1; % 判断动荡程度（第二种）
    while i<=n
        if data(i) ~= 1
            flag = length(find(data(i:i+sigma2-1) ~= 1));
            if flag >= lamda
                i = i+sigma2;
            else
                data(i) = 1;
            end
        end
        i = i+1;
    end
end
% 判断是否有数据
if isempty(data)
    output = [];
    return;
else
%% 延迟机制
DATA(:,1) = find(data~=1); DATA(:,2) = data(data~=1);
output = []; j = 1; output(j,:) = DATA(1,:);
for i = 2:length(DATA)
    if  DATA(i,1)-output(j,1) < sigma1
        [~,m] = max([DATA(i,2) output(j,2)]);
        if m == 1
            output(j,:) = DATA(i,:);
        end
    else
        j = j+1;
        output(j,:) = DATA(i,:);
    end
end
end