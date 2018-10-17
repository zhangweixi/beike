function y=coo_sat(t)      %%%%输入格式为[5 09  3 16  10 00 0.0]的卫星号和时间
format long
[fname,filepath] = uigetfile('*.09N','星历文件数据文件名');
fid=fopen(strcat(filepath,fname),'rt');
dat0=textread(strcat(filepath,fname),'%s',10000);     %以字符串形式读到文件结束
n=24;
dat0=dat0(n+1:end,:);
sz=size(dat0);
for i=1:sz(1)
    dat(i)=str2num(str2mat(dat0(i)));
end
PRN=[];Y=[];M=[];D=[];H=[];MIN=[];SEC=[];
for i = 0:sz(1)/38-1
   PRN(i+1)=dat(1+38*i);Y(i+1)=dat(2+38*i);M(i+1)=dat(3+38*i);D(i+1)=dat(4+38*i);H(i+1)=dat(5+38*i);MIN(i+1)=dat(6+38*i);SEC(i+1)=dat(7+38*i);
end
% time0=inputdlg('请输入星历号和时间信息.如:9 09 3 17 8 0 0.0','输入')
%     time=fscanf(time0,'%f',7);
v=[];
for i = 1:sz(1)/38
    if t(1)==PRN(i)&t(2)==Y(i)&t(3)==M(i)&t(4)==D(i)&abs(t(5)*60+t(6)-H(i)*60-MIN(i))<60;
       v=i;
       break
    end
end
if isempty(v);
    msgbox('无法用此广播星历文件计算此时此时刻该星的坐标或计算不准确','提示');
    return
end
if t(2)>20
    t(2)=t(2)+1900;
else
    t(2)=t(2)+2000;
end
y=t(2);m=t(3);d=t(4);h=t(5);min=t(6);sec=t(7);
af0=dat(8+(v-1)*38);af1=dat(9+(v-1)*38);af2=dat(10+(v-1)*38);
aode=dat(11+(v-1)*38);Crs=dat(12+(v-1)*38);deltan=dat(13+(v-1)*38);M0=dat(14+(v-1)*38);
Cuc=dat(15+(v-1)*38);
e=dat(16+(v-1)*38);Cus=dat(17+(v-1)*38);sqra=dat(18+(v-1)*38);
toe=dat(19+(v-1)*38);Cic=dat(20+(v-1)*38);omu_0=dat(21+(v-1)*38);Cis=dat(22+(v-1)*38);
i0=dat(23+(v-1)*38);Crc=dat(24+(v-1)*38);omg=dat(25+(v-1)*38);omu_d=dat(26+(v-1)*38);
iDot=dat(27+(v-1)*38);cflgl2=dat(28+(v-1)*38);weekno=dat(29+(v-1)*38);
% pflgl2=dat(30);svacc=dat(31);svhlth=dat(32);tgd=dat(33);aodc=dat(34);ttm=dat(35);
GM=3.9860044181e14;
omu_e=7.292155e-5;

a=sqra^2;  %长半轴
n0=(GM/a^3)^0.5;        %平运动
if m<=2
y=y-1; m=m+12;
end
ut=h+min/60+sec/3600;
jd=fix(365.25*y)+fix(30.6001*(m+1))+d+ut/24+1720981.5;      %%%%%%%%%把t换算成GPS时
GPSsec=(jd-2444244.5-7*weekno)*24*3600;
tk=GPSsec-toe;
deltat=af0+af1*tk+af2*tk^2;
tk=tk+deltat;
if tk>302400
    tk=tk-604800;
elseif tk<-302400
    tk=tk+604800;
end
n=n0+deltan;
Mk=M0+n*tk;
Ek0=Mk;
Ek=0;
while abs(Ek-Ek0)>1e-12              %偏近点角迭代解算
    Ek0=Ek;
    Ek=Mk+e*sin(Ek0);
end
fs=atan2(sqrt(1-e^2)*sin(Ek),(cos(Ek)-e));      %真近点角
% if sqrt(1-e^2)*sin(Ek)<0 & (cos(Ek)-e)<0
%     fs=pi+fs
% elseif sqrt(1-e^2)*sin(Ek)<0 & (cos(Ek)-e)>0
%     fs=pi+fs
% elseif sqrt(1-e^2)*sin(Ek)>0 & (cos(Ek)-e)<0
%     fs=pi*2+fs
% else
%     fs=fs
% end
uk=fs+omg ;  %升交距角
delta_uk=Cuc*cos(2*uk)+Cus*sin(2*uk);  %升交角距二阶摄动
delta_rk=Crc*cos(2*uk)+Crs*sin(2*uk);  %地心向径二阶摄动
delta_ik=Cic*cos(2*uk)+Cis*sin(2*uk);  %倾角二阶摄动
u=uk+delta_uk;      %改正后的升交角距
r=a*(1-e*cos(Ek))+delta_rk;%改正后的地心向径
i=i0+delta_ik+iDot*tk; %改正后的倾角
x=r*cos(u);
y=r*sin(u);
% omu=omu_0+omu_d*tk
we=7.29211567e-5;
lamda=omu_0+(omu_d-we)*tk-we*toe;
R=[cos(lamda) -sin(lamda)*cos(i)
    sin(lamda) cos(lamda)*cos(i)
    0 sin(i)];
XYZ=R*[x;y]