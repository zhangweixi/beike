function MK = Read(A)
fidin=fopen('A.txt');                            % ��test2.txt�ļ�             
fidout=fopen('mkmatlab.txt','w');                % ����MKMATLAB.txt�ļ�
while ~feof(fidin)                               % �ж��Ƿ�Ϊ�ļ�ĩβ               
    tline=fgetl(fidin);                          % ���ļ�����   
    fprintf(fidout,'%s\n',tline);                % ����������У��Ѵ�������д���ļ�MKMATLAB.txt
end
fclose(fidout);
MK = importdata('mkmatlab.txt')/1000;  
end




% fid=fopen('D:\ʵϰ��Ŀ-��̬ʶ��\�����㷨\data\data\b.txt','wt');%д���ļ�·��
% matrix = MK;                       %input_matrixΪ���������
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