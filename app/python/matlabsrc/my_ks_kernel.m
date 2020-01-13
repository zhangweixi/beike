function K = my_ks_kernel(X1,X2,ktype,kpar)
% KM_KERNEL calculates the kernel matrix between two data sets.
% Input:	- X1, X2: data matrices in row format (data as rows) 
%             在差分运算中，X1为观测样本，X2 是待估计点
%			- ktype: string representing kernel type
%			- kpar: vector containing the kernel parameters
%           - diff_ord: 核函数的差分阶数，为了构造差分滤波器用，默认0，不差分；
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
        g  = exp(-(x-y)^2/(2*sgm^2));  % 构造符号高斯核函数，以便于求导
%         gd = diff(g,y,diff_ord);       % 对x求 diff_ord 阶差分
        gf = matlabFunction(g);       % 将符号函数转化为数值函数
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
        g  = exp(-(x-u)^2/(2*s^2));    % 构造符号高斯核函数，以便于求导
        gd = diff(g,x,diff_ord);       % 对x求 diff_ord 阶差分
        gf = matlabFunction(gd);       % 将符号函数转化为数值函数
        K = feval(gf,X1,X2,sgm);       %
        K =K';
 
    case 'poly'	        % polynomial kernel
        p = kpar(1);	% polynome order
        c = kpar(2);	% additive constant
        
        syms x u;
        g  = (x*u+c).^p;  % 构造符号高斯核函数，以便于求导
        gd = diff(g,u,diff_ord);       % 对x求 diff_ord 阶差分
        gf = matlabFunction(gd);       % 将符号函数转化为数值函数
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
