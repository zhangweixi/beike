%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 2018-10-28
% ����Э���������Բ
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% clc; clear all;
% pathname = 'D:\ʵϰ��Ŀ-��̬ʶ��\Data ';
% sensor_R = 'sensor-R.txt'; sensor_L = 'sensor-L.txt'; gps_L = 'gps-L.txt';
% % ���·��
% addpath(genpath(pathname)); 
% % Sensor
% sensor_r = importdata(sensor_R)/1000; 
% data1 = sensor_r(:,1)-1; data2 = sensor_r(:,2); data3 = sensor_r(:,3); ci = 0.999;
function singular = error_ellipse3(data1,data2,data3,ci)
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% ���룺data1 Ϊ������ data2 Ϊ������ data3 Ϊ������
% ����������
% Calculate the eigenvectors and eigenvalues
data = [data1,data2,data3];
covariance = cov(data);
[eigenvec,eigenval] = eig(covariance);

% Get the index of the eigenvector
[B,I] = sort(sort(eigenval,2,'descend'),1,'descend');
largest_eigenvec = eigenvec(:,I(1,1)); middle_eigenvec = eigenvec(:,I(2,1)); smallest_eigenvec = eigenvec(:,I(3,1));

% Get the eigenvalue
largest_eigenval = B(1,1); middle_eigenval = B(2,1); smallest_eigenval = B(3,1);

% Get the coordinates of the data mean
avg = mean(data);

% Get the  confidence interval error ellipse
if (ci<0.95)&&(ci>=1)
    return
else
    if ci<0.975
        chisquare_val = 2.7955;
    else
        if ci<0.99
            chisquare_val = 3.057;
        else
            if ci<0.995
                chisquare_val = 3.3682;
            else
                chisquare_val = 3.5830;
            end
        end
    end
end
X0 = avg(1); Y0 = avg(2); Z0 = avg(3);
a = chisquare_val*sqrt(largest_eigenval);
b = chisquare_val*sqrt(middle_eigenval);
c = chisquare_val*sqrt(smallest_eigenval);
Largest_eigenvec = largest_eigenvec/norm(largest_eigenvec)*a;
Middle_eigenvec = middle_eigenvec/norm(middle_eigenvec)*b;
Smallest_eigenvec = smallest_eigenvec/norm(smallest_eigenvec)*c;
% Define a rotation matrix
R = [Largest_eigenvec,Middle_eigenvec,Smallest_eigenvec]'\[a 0 0;0 b 0;0 0 c];

% Determine if the ellipse is exceeded
k = 1; singular = [];
for i = 1:length(data)
    coordinate = [data(i,1)-X0,data(i,2)-Y0,data(i,3)-Z0]*R;
    if (coordinate(1)/a)^2 + (coordinate(2)/b)^2 + (coordinate(3)/c)^2 > 1
        singular(k,:) = [i,data(i,1),data(i,2),data(i,3)];
        k = k+1;
    end
end
if isempty(singular)
    return;
end
end
