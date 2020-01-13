%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 2018-09-13
% 判断类型参数
%%%%%%%%%%%%%%%%%%%%%%%%%%%
function [Output,Order_output] = Type(pass,parameter1,parameter2,parameter3)
[h,~] = size(pass);
for i = 1:h
    switch pass(i,2)
        case 1            % 1-长传
            data1(k,:) = pass(i,:);
            k = k+1;
        case 2            % 2-短传
            data2(kk,:) = pass(i,:);
            kk = kk+1;
        case 3            % 3-长传
            data3(kkk,:) = pass(i,:);
            kkk = kkk+1;  
    end
end
DATA1 = Interval(data1,parameter1);   % 1-长传
DATA2 = Interval(data2,parameter2);   % 2-短传
DATA3 = Interval(data3,parameter3);   % 3-长传
Output = [DATA1;DATA2;DATA3];
[~,I] = sort(Output(:,3),'ascend');
Order_output = Output(I,:);
end