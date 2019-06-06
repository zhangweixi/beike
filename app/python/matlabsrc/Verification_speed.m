%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 2019-01-15
% 踢球速度验证
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function output = Verification_speed(PASS,speed3,speed1)
output = [];
% 判断有没有数据
if isempty(PASS)
    return;
end
[m,~] = size(PASS);k = 1;
for i = 1:m
    % 验证触球
    if PASS(i,2) == 3
        if PASS(i,7) <= speed3 
            output(k,:) = PASS(i,:);
            k = k+1;
        end
    end
    % 验证短传
    if PASS(i,2) == 2
        if (PASS(i,7) > speed3) && (PASS(i,7) <= speed1)
            output(k,:) = PASS(i,:);
            k = k+1;
        else
%             if PASS(i,7) <= speed3
%                 output(k,:) = PASS(i,:);
%                 output(k,2) = 3;
%                 k = k+1;
%             end
            if PASS(i,7) > speed1
                output(k,:) = PASS(i,:);
                output(k,2) = 1;
                k = k+1;
            end
        end
    end
%     % 验证短传
%     if PASS(i,2) == 2
%         if  PASS(i,7) <= speed1
%             output(k,:) = PASS(i,:);
%             k = k+1;
%         else
%             output(k,:) = PASS(i,:);
%             output(k,2) = 1;
%             k = k+1;
%         end
%     end
    % 验证长传
    if PASS(i,2) == 1
        if PASS(i,7) > speed1
            output(k,:) = PASS(i,:);
            k = k+1;
        else
            output(k,:) = PASS(i,:);
            output(k,2) = 2;
            k = k+1;
    end
end
end