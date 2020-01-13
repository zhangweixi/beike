%%%%%%%%%%%%%%%%%%%%%%%%
% �жϴ������ 
% 2018-01-18
%%%%%%%%%%%%%%%%%%%%%%%%
function flag = Contact_times(input)
%% �жϴ������ 
flag = zeros(1,3); speed3 = []; speed2 = []; speed1 = [];
[m,~] = size(input);
for i = 1:m
    if input(i,2) == 3
        flag(1,1) = flag(1,1)+1;
        speed3(flag(1,1)) = input(i,7);
    end
    if input(i,2) == 2
        flag(1,2) = flag(1,2)+1;
        speed2(flag(1,2)) = input(i,7);
    end
    if input(i,2) == 1
        flag(1,3) = flag(1,3)+1;
        speed1(flag(1,3)) = input(i,7);
    end
end  
figure
title('�����ٶ���״ͼ');
subplot(3,1,1)
if ~isempty(speed1) 
    bar(speed1*3.6,'r'); xlabel('��������'); ylabel('km/h');
end
subplot(3,1,2)
if ~isempty(speed2)
    bar(speed2*3.6,'k'); xlabel('�̴�����'); ylabel('km/h');
end
subplot(3,1,3)
if ~isempty(speed3) 
    bar(speed3*3.6,'b'); xlabel('�������'); ylabel('km/h');
end
end