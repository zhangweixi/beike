function K = my_ks_kernel(X1,X2,ktype,kpar)
% KM_KERNEL calculates the kernel matrix between two data sets.
% Input:	- X1, X2: data matrices in row format (data as rows) 
%             �ڲ�������У�X1Ϊ�۲�������X2 �Ǵ����Ƶ�
%			- ktype: string representing kernel type
%			- kpar: vector containing the kernel parameters
%           - diff_ord: �˺����Ĳ�ֽ�����Ϊ�˹������˲����ã�Ĭ��0������֣�
% Output:	- K: kernel matrix
% USAGE: K = km_kernel(X1,X2,ktype,kpar)
%
% Author: Steven Van Vaerenbergh (steven *at* gtas.dicom.unican.es), 2012.
%
% This file is part of the Kernel Methods Toolbox for MATLAB.
% https://github.com/steven2358/kmbox

% k = length(X1);
% m = length(X2);
switch ktype
    
    case 'gauss'	% Gaussian kernel
        
        sgm = kpar;	% kernel width   
        syms x y;
        g  = exp(-(x-y)^2/(2*sgm^2));  % ������Ÿ�˹�˺������Ա�����
%         gd = diff(g,y,diff_ord);       % ��x�� diff_ord �ײ��
        gf = matlabFunction(g);       % �����ź���ת��Ϊ��ֵ����
        K = feval(gf,X1,X2); 
        K =K'; 
%         for i = 1:k
%             for j = 1:m
%                 K(i,j) = feval(gf,X1(i),X2(j));     
%             end
%         end              
%         K =K';
        
    case 'gauss-diag'	% only diagonal of Gaussian kernel
        sgm = kpar;	    % kernel width 
        syms x u s;
        g  = exp(-(x-u)^2/(2*s^2));    % ������Ÿ�˹�˺������Ա�����
        gd = diff(g,x,diff_ord);       % ��x�� diff_ord �ײ��
        gf = matlabFunction(gd);       % �����ź���ת��Ϊ��ֵ����
        K = feval(gf,X1,X2,sgm);       %
        K =K';
 
    case 'poly'	        % polynomial kernel
        p = kpar(1);	% polynome order
        c = kpar(2);	% additive constant
        
        syms x u;
        g  = (x*u+c).^p;  % ������Ÿ�˹�˺������Ա�����
        gd = diff(g,u,diff_ord);       % ��x�� diff_ord �ײ��
        gf = matlabFunction(gd);       % �����ź���ת��Ϊ��ֵ����
        K = feval(gf,X1,X2);           %
        K =K';
        
    case 'linear' % linear kernel
        
        if diff_ord == 0
            K = (X1.*X2)';
        elseif diff_ord == 1
            K = X1';
        else
            K = X1'*0;
        end
        
    otherwise	% default case
        error ('unknown kernel type')
end
