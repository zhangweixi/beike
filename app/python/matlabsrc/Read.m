function MK = Read(A)
fidin=fopen('A.txt');                            % 打开test2.txt文件             
fidout=fopen('mkmatlab.txt','w');                % 创建MKMATLAB.txt文件
while ~feof(fidin)                               % 判断是否为文件末尾               
    tline=fgetl(fidin);                          % 从文件读行   
    fprintf(fidout,'%s\n',tline);                % 如果是数字行，把此行数据写入文件MKMATLAB.txt
end
fclose(fidout);
MK = importdata('mkmatlab.txt')/1000;  
end




% fid=fopen('D:\实习项目-步态识别\澜启算法\data\data\b.txt','wt');%写入文件路径
% matrix = MK;                       %input_matrix为待输出矩阵
% [m,n]=size(matrix);
%     for i=1:1:m
%         for j=1:1:n
%            if j==n
%              fprintf(fid,'%g\n',matrix(i,j));
%            else
%             fprintf(fid,'%g\t',matrix(i,j));
%          end
%       end
%      end
%      fclose(fid);