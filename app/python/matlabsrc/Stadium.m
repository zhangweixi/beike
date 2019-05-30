%%%%%%%%%%%%%%%%%%%%%%%
% 规划球场
% 2018-09-16
%%%%%%%%%%%%%%%%%%%%%%%
function Stadium(pathname,cour,point)
% 参考输入模式如下：
% pathname = 'G:\'; cour = 'cour.txt'; point = 'point.txt';
%% 读数据
% 添加路径
addpath(genpath(pathname)); 
data = importdata(cour);  
Coordinate = getfield(data,'data');
Label = getfield(data,'textdata');
n = length(Coordinate);
%% 
a = 0; b = 0; c = 0; d = 0; e = 0; f =0;
for i = 1:n
    switch char(Label(i))
        case 'A'
            a = a+1;
        case 'B'
            b = b+1;
        case 'C'
            c = c+1;
        case 'D'
            d = d+1;
        case 'E'
            e = e+1;
        case 'F'
            f = f+1;
    end
end
%% 方案一
P_EF = polyfit(Coordinate(a+b+c+d+1:a+b+c+d+e,1),Coordinate(a+b+c+d+1:a+b+c+d+e,2),1);
K_EF = P_EF(1); D_EF = P_EF(2);
K_AD = K_EF; K_DE = -1/K_EF; K_FA = K_DE;
D_AD = mean(Coordinate(1:a+b+c,2))-K_AD*mean(Coordinate(1:a+b+c,1));
D_DE = mean(Coordinate(a+b+c+1:a+b+c+d,2))-K_DE*mean(Coordinate(a+b+c+1:a+b+c+d,1));
D_FA = mean(Coordinate(a+b+c+d+e+1:a+b+c+d+e+f,2))-K_FA*mean(Coordinate(a+b+c+d+e+1:a+b+c+d+e+f,1));
D_BC = mean(Coordinate(a+1:a+b,2))-K_DE*mean(Coordinate(a+1:a+b,1));
D_B = Coordinate(a+1,2)-K_DE*Coordinate(a+1,1);
D_C = Coordinate(a+b+1,2)-K_DE*Coordinate(a+b+1,1);
%% 方案二
% P_BC = polyfit(Coordinate(1:a+b+c,1),Coordinate(1:a+b+c,2),1);
% K_AD = P_BC(1); D_AD = P_BC(2);
% K_DE = -1/K_AD; K_EF = K_AD; K_FA = K_DE;
% D_DE = mean(Coordinate(a+b+c+1:a+b+c+d,2))-K_DE*mean(Coordinate(a+b+c+1:a+b+c+d,1));
% D_EF = mean(Coordinate(a+b+c+d+1:a+b+c+d+e,2))-K_DE*mean(Coordinate(a+b+c+d+1:a+b+c+d+e,1));
% D_FA = mean(Coordinate(a+b+c+d+e+1:a+b+c+d+e+f,2))-K_FA*mean(Coordinate(a+b+c+d+e+1:a+b+c+d+e+f,1));
%%
syms x y
vars = [x y];
eqns1 = [K_AD*x+D_AD == y , K_FA*x+D_FA == y];  % A
eqns2 = [K_AD*x+D_AD == y , K_DE*x+D_B == y];   % B
eqns3 = [K_AD*x+D_AD == y , K_DE*x+D_C == y];   % C
eqns4 = [K_AD*x+D_AD == y , K_DE*x+D_DE == y];  % D
eqns5 = [K_EF*x+D_EF == y , K_DE*x+D_DE == y];  % E
eqns6 = [K_EF*x+D_EF == y , K_FA*x+D_FA == y];  % F
eqns7 = [K_AD*x+D_AD == y , K_DE*x+D_BC == y];  % BC
eqns8 = [K_EF*x+D_EF == y , K_DE*x+D_BC == y];  % Center_BC
eqns9 = [K_EF*x+D_EF == y , K_DE*x+D_B == y];   % Center_B
eqns10 = [K_EF*x+D_EF == y , K_DE*x+D_C == y];  % Center_C
[A_lat,A_lon] = solve(eqns1, vars); [B_lat,B_lon] = solve(eqns2, vars); 
[C_lat,C_lon] = solve(eqns3, vars); [D_lat,D_lon] = solve(eqns4, vars);
[E_lat,E_lon] = solve(eqns5, vars); [F_lat,F_lon] = solve(eqns6, vars);
[BC_lat,BC_lon] = solve(eqns7, vars); [bc_lat,bc_lon] = solve(eqns8, vars); 
[b_lat,b_lon] = solve(eqns9, vars); [c_lat,c_lon] = solve(eqns10, vars); 
DD_lat = 2*E_lat-D_lat; DD_lon = 2*E_lon-D_lon;
AA_lat = 2*F_lat-A_lat; AA_lon = 2*F_lon-A_lon; 
Sym_BC_lat = 2*bc_lat-BC_lat; Sym_BC_lon = 2*bc_lon-BC_lon;
Sym_B_lat = 2*b_lat-B_lat; Sym_B_lon = 2*b_lon-B_lon;
Sym_C_lat = 2*c_lat-C_lat; Sym_C_lon = 2*c_lon-C_lon;
A = double([A_lat A_lon]); B = double([B_lat B_lon]); BC = double([BC_lat BC_lon]);C = double([C_lat C_lon]);D = double([D_lat D_lon]);
E = double([E_lat E_lon]); F = double([F_lat F_lon]); Sym_D = double([DD_lat DD_lon]); Sym_B = double([Sym_B_lat Sym_B_lon]); 
Sym_BC = double([Sym_BC_lat Sym_BC_lon]); Sym_C = double([Sym_C_lat Sym_C_lon]); Sym_A = double([AA_lat AA_lon]);
Point = {'A' A;'B' B;'BC' BC;'C' C;'D' D;'E' E;'F' F;'Sym_D' Sym_D;'Sym_B' Sym_B;'Sym_BC' Sym_BC;'Sym_C' Sym_C;'Sym_A' Sym_A};
% T = cell2table(Point);
Location = [pathname,point];
% writetable(T,Location,'Delimiter',' ');
%%
fileID = fopen(Location,'w');
formatSpec = '%s %.6f %.6f\n';
[nrows,~] = size(Point);
for row = 1:nrows
    fprintf(fileID,formatSpec,Point{row,:}); 
end
fclose(fileID);
end



