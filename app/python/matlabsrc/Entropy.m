%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% ��Ϣ��
% 2018-10-26
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function  Hx = Entropy(signal,flag)
%--------------------------------------------------
% ����ԭ�ź�Ϊ�ο���ʱ������ź���
% ����:    y:������Ϣ�ص�����
%       flag:������Ϣ�ص�����Ҫ���ֿ�Ŀ���
% �����  Hx:y����Ϣ��
%--------------------------------------------------
x_min = min(signal); x_max = max(signal);
maxf(1) = abs(x_max - x_min); % ԭ�źŵ����������������ĵ�
maxf(2) = x_min;
duan_t = 1.0 / flag;
jiange = maxf(1) * duan_t;
% for i=1:10
%     pnum(i) = length(find((y_p>=(i-1)*jiange)&(y_p<i*jiange)));
% end
pnum(1) = length(find(signal<maxf(2)+jiange));
for i = 2:flag-1
    pnum(i) = length(find((signal>=maxf(2)+(i-1)*jiange)&(signal<maxf(2)+i*jiange)));
end
pnum(flag) = length(find(signal>=maxf(2)+(flag-1)*jiange));
% sum(pnum)
ppnum  =pnum/sum(pnum); % ÿ�γ��ֵĸ���
% sum(ppnum)
Hx = 0;
for i = 1:flag
    if ppnum(i) == 0
        Hi = 0;
    else
        Hi = -ppnum(i)*log2(ppnum(i));
    end
    Hx = Hx+Hi;
end
end