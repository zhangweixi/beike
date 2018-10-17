function structxlswrite(xlsfile,stData)
ceData = struct2cell(stData);
if size(ceData, 2) > 1
    error('第二个参数不能为结构体数组');
end
for i = 1 : length(ceData)
    buf = ceData{i};    
    % 若当前cell为数组，则将数组拆开存储
    if isnumeric(buf)
        for j = 1 : length(ceData{i})
            ceData{i,j}= buf(j);
        end
    end
end
% 将结构体的数据写入指定的excel文件里
xlswrite(xlsfile,ceData);
end
