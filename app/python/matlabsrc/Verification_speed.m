%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 2019-01-15
% �����ٶ���֤
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function output = Verification_speed(PASS,speed3,speed1)
output = [];
% �ж���û������
if isempty(PASS)
    return;
end
[m,~] = size(PASS);k = 1;
for i = 1:m
    % ��֤����
    if PASS(i,2) == 3
        if PASS(i,7) <= speed3 
            output(k,:) = PASS(i,:);
            k = k+1;
        end
    end
    % ��֤�̴�
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
%     % ��֤�̴�
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
    % ��֤����
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