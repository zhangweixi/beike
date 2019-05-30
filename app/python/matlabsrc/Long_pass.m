%%%%%%%%%%%%%%%%%%%%%%%%%%
% �жϳ���
% 2018-11-12
%%%%%%%%%%%%%%%%%%%%%%%%%%
function longpass3 = Long_pass(data,amplitude,interval,touchnumber,fs,long)
%% �ж���û������
if isempty(data)
    longpass3 = [];
    return;
end
[l,~] = size(data); 
%% ���շ�ֵɸѡ��һ��
longpass1 = data(data(:,6) >= amplitude,:);
% �ж���û������
if isempty(longpass1)
    longpass3 = [];
    return;
end
%% ����ʱ����ɸѡ�ڶ���
[m,~] = size(longpass1); 
j = 1; longpass2 = []; longpass2(j,:) = longpass1(1,:);
for i = 2:m
    if  longpass1(i,1) - longpass2(j,1) < interval * fs
        [~,z] = max([longpass1(i,2),longpass2(j,2)]);
        if z == 1
            longpass2(j,:) = longpass1(i,:);
        end
    else
        j = j+1;
        longpass2(j,:) = longpass1(i,:);
    end
end
% �ж���û������
if isempty(longpass2)
    longpass3 = [];
    return;
end
% ���ռ���������ɸѡ������
[m,~] = size(longpass2); longpass3 = [];
i = 1; k = 1; Inter = interval * fs;
while i <= m
    if longpass2(i,1)-Inter < 0
        min_time = 0;
    else
        min_time = longpass2(i,1)-Inter;
    end
    if longpass2(i,1)+Inter > long
        max_time = long;
    else
        max_time = longpass2(i,1)+Inter;
    end
    number = 0;
    for j = 1:l
        if (data(j,1) > min_time)&&(data(j,1) < max_time)
            number = number+1;
        end
    end
    if number < touchnumber
        longpass3(k,:) = longpass2(i,:); k = k+1;
    end
    i = i+1;
end
end