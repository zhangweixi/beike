%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 2018-10-31
% �ж��𵴳̶�
%%%%%%%%%%%%%%%%%%%%%%%%%%%
function output = vibrate(data,lamda,sigma1,sigma2)
n = length(data); output = [];
if (nargin < 4)
    i = 1; % �ж϶����̶ȣ���һ�֣�
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
    i = 1; % �ж϶����̶ȣ��ڶ��֣�
    while i <= n
        if data(i) ~= 1
            Top = i+sigma2-1;
            if Top > n
                flag = length(find(data(i:n) ~= 1));
            else
                flag = length(find(data(i:Top) ~= 1));
            end
            if flag >= lamda
                i = i+sigma2;
            else
                data(i) = 1;
            end
        end
        i = i+1;
    end
end
%% �ӳٻ���
DATA(:,1) = find(data~=1); DATA(:,2) = data(data~=1);
% �ж��Ƿ�������
if isempty(DATA)
    return;
end
output = []; j = 1; output(j,:) = DATA(1,:); [l,~] = size(DATA);
if l > 2
    for i = 2:l
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
else
    output = DATA;
end
end