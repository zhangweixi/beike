function structxlswrite(xlsfile,stData)
ceData = struct2cell(stData);
if size(ceData, 2) > 1
    error('�ڶ�����������Ϊ�ṹ������');
end
for i = 1 : length(ceData)
    buf = ceData{i};    
    % ����ǰcellΪ���飬������𿪴洢
    if isnumeric(buf)
        for j = 1 : length(ceData{i})
            ceData{i,j}= buf(j);
        end
    end
end
% ���ṹ�������д��ָ����excel�ļ���
xlswrite(xlsfile,ceData);
end
