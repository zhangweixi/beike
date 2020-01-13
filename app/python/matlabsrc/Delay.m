%%%%%%%%%%%%%%%%%%%%%%%
% ÑÓ³Ù»úÖÆ
% 2018-11-13
%%%%%%%%%%%%%%%%%%%%%%%
function output = Delay(data,internal,fs)
if isempty(data)
    output = null;
    return;
end
output = []; j = 1; [m,~] = size(data);
output(j,:) = data(1,:);
for i = 2:m
    if  data(i,1) - output(j,1) < internal * fs
        [~,n] = max([data(i,2),output(j,2)]);
        if n == 1
            output(j,:) = data(i,:);
        end
    else
        j = j+1;
        output(j,:) = data(i,:);
    end
end
end
