%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 信息熵
% 2018-10-26
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function  Hx = Entropy(signal,flag)
%--------------------------------------------------
% 不以原信号为参考的时间域的信号熵
% 输入:    y:待求信息熵的序列
%       flag:待求信息熵的序列要被分块的块数
% 输出：  Hx:y的信息熵
%--------------------------------------------------
x_min = min(signal); x_max = max(signal);
maxf(1) = abs(x_max - x_min); % 原信号的能量谱中能量最大的点
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
ppnum  =pnum/sum(pnum); % 每段出现的概率
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