%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 划分球场
% 2018-09-14
%%%%%%%%%%%%%%%%%%%%%%%%%%
function Point = ParkBall(data)
%{
    polyfit函数是matlab中用于进行曲线拟合的一个函数。其数学基础是最小二乘法曲线拟合原理。
    调用方法：polyfit(x,y,n)。用多项式求过已知点的表达式，
    其中x为源数据点对应的横坐标，可为行向量、矩阵；
        y为源数据点对应的纵坐标，可为行向量、矩阵；
        n为你要拟合的阶数，一阶直线拟合，二阶抛物线拟合
%}
syms x y
vars = [x y];
p = polyfit(data(1:4,1),data(1:4,2),1);
lat_de = (data(4,1)+data(5,1))/2; lon_de = (data(4,2)+data(5,2))/2;
lat_ef = (data(5,1)+data(6,1))/2; lon_ef = (data(5,2)+data(6,2))/2;
lat_af = (data(1,1)+data(6,1))/2; lon_af = (data(1,2)+data(6,2))/2;
K_ad = p(1); D_ad = p(2);
K_de = -1/K_ad; D_de = lon_de-K_de*lat_de;
K_ef = K_ad; D_ef = lon_ef-K_ef*lat_ef;
K_af = K_de; D_af = lon_af-K_af*lat_af;
eqns1 = [K_ad*x+D_ad == y , K_af*x+D_af == y];
eqns2 = [K_ad*x+D_ad == y , K_de*x+D_de == y];
eqns3 = [K_ef*x+D_ef == y , K_de*x+D_de == y];
eqns4 = [K_ef*x+D_ef == y , K_af*x+D_af == y];
[A_lat,A_lon] = solve(eqns1, vars); [D_lat,D_lon] = solve(eqns2, vars); 
[E_lat,E_lon] = solve(eqns3, vars); [F_lat,F_lon] = solve(eqns4, vars);
Point = double([A_lat A_lon;D_lat D_lon;E_lat E_lon;F_lat F_lon]);
end











% %{
%     MATLAB中的插值函数为interp1，其调用格式为：  yi= interp1(x,y,xi,'method')           
%     其中x，y为插值点，yi为在被插值点xi处的插值结果；x,y为向量， 
%     'method'表示采用的插值方法，MATLAB提供的插值方法有几种： 
%         'nearest'是最邻近插值， 'linear'线性插值； 'spline'三次样条插值； 'pchip'立方插值．缺省时表示线性插值
%     注意：所有的插值方法都要求x是单调的，并且xi不能够超过x的范围。
% %}
% y2 = interp1(x,y,x2,'spline');
% subplot(1,2,2);
% plot(x1,y1,'k',x2,y2,'r')
% xlabel('时间（秒）');
% ylabel('位移（米）');
% title('黑线为最小二乘法拟合，红色为插值法拟合')  
% grid on





% end