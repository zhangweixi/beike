%%%%%%%%%%%%%%%%%%%%%%%%%%%
% ������
% 2018-09-14
%%%%%%%%%%%%%%%%%%%%%%%%%%
function Point = ParkBall(data)
%{
    polyfit������matlab�����ڽ���������ϵ�һ������������ѧ��������С���˷��������ԭ��
    ���÷�����polyfit(x,y,n)���ö���ʽ�����֪��ı��ʽ��
    ����xΪԴ���ݵ��Ӧ�ĺ����꣬��Ϊ������������
        yΪԴ���ݵ��Ӧ�������꣬��Ϊ������������
        nΪ��Ҫ��ϵĽ�����һ��ֱ����ϣ��������������
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
%     MATLAB�еĲ�ֵ����Ϊinterp1������ø�ʽΪ��  yi= interp1(x,y,xi,'method')           
%     ����x��yΪ��ֵ�㣬yiΪ�ڱ���ֵ��xi���Ĳ�ֵ�����x,yΪ������ 
%     'method'��ʾ���õĲ�ֵ������MATLAB�ṩ�Ĳ�ֵ�����м��֣� 
%         'nearest'�����ڽ���ֵ�� 'linear'���Բ�ֵ�� 'spline'����������ֵ�� 'pchip'������ֵ��ȱʡʱ��ʾ���Բ�ֵ
%     ע�⣺���еĲ�ֵ������Ҫ��x�ǵ����ģ�����xi���ܹ�����x�ķ�Χ��
% %}
% y2 = interp1(x,y,x2,'spline');
% subplot(1,2,2);
% plot(x1,y1,'k',x2,y2,'r')
% xlabel('ʱ�䣨�룩');
% ylabel('λ�ƣ��ף�');
% title('����Ϊ��С���˷���ϣ���ɫΪ��ֵ�����')  
% grid on





% end