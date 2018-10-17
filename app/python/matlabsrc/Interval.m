%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 判断最大时间间隔
% 2018-09-10
%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function PASS = Interval(data,parameter)
% 1-长传、2-短传、3-触球。
[h,~] = size(data);k = 1;
PASS(k,:) = data(1,:);
for i = 2:h
   if diff([PASS(k,3) data(i,3)])>parameter
       k = k+1;
       PASS(k,:) = data(i,:);     
   else
       if data(i,4)>PASS(k,4)
           PASS(k,:) = data(i,:);
       end
   end
end
end