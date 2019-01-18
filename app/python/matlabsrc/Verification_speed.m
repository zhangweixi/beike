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
%     if PASS(i,2) == 2
%         if (PASS(i,7) > speed3) && (PASS(i,7) <= speed1)
%             output(k,:) = PASS(i,:);
%             k = k+1;
%         else
%             if PASS(i,7) <= speed3
%                 output(k,:) = PASS(i,:);
%                 output(k,2) = 3;
%                 k = k+1;
%             end
%             if PASS(i,7) > speed1
%                 output(k,:) = PASS(i,:);
%                 output(k,2) = 1;
%                 k = k+1;
%             end
%         end
%     end
    % 验证短传
    if PASS(i,2) == 2
        if  PASS(i,7) <= speed1
            output(k,:) = PASS(i,:);
            k = k+1;
        else
            output(k,:) = PASS(i,:);
            output(k,2) = 1;
            k = k+1;
        end
    end
    % 验证长传
    if PASS(i,2) == 1
        if PASS(i,7) > speed1
            output(k,:) = PASS(i,:);
            k = k+1;
        end
    end
end
% % 判断触球次数 
% flag = zeros(1,3); speed3 = []; speed2 = []; speed1 = [];
% for i = 1:length(output)
%     if output(i,2) == 3 
%         flag(1,1) = flag(1,1)+1;
%         speed3(flag(1,1)) = output(i,7);
%     end
%     if output(i,2) == 2
%         flag(1,2) = flag(1,2)+1;
%         speed2(flag(1,2)) = output(i,7);
%     end
%     if output(i,2) == 1
%         flag(1,3) = flag(1,3)+1;
%         speed1(flag(1,3)) = output(i,7);
%     end
% end  
% figure
% title('触球速度柱状图');
% subplot(3,1,1)
% bar(speed1,'r'); xlabel('长传次数'); ylabel('m/s');
% subplot(3,1,2)
% bar(speed2,'k'); xlabel('短传次数'); ylabel('m/s');
% subplot(3,1,3)
% bar(speed3,'b'); xlabel('触球次数'); ylabel('m/s');
end