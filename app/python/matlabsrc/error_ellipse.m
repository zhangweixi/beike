%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 2018-10-28
% 根据协方差矩阵画椭圆
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% clc; clear all;
% pathname = 'D:\实习项目-步态识别\Data ';
% sensor_R = 'sensor-R.txt'; sensor_L = 'sensor-L.txt'; gps_L = 'gps-L.txt';
% % 添加路径
% addpath(genpath(pathname)); 
% % Sensor
% sensor_r = importdata(sensor_R)/1000; 
% data1 = sensor_r(:,1); data2 = sensor_r(:,2); ci = 0.99;
function singular = error_ellipse(data1,data2,ci)
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 输入：data1 为列向量 data2 为列向量
% 输出：触球点
% Calculate the eigenvectors and eigenvalues
data = [data1,data2];
covariance = cov(data);
[eigenvec, eigenval ] = eig(covariance);

% Get the index of the largest eigenvector
[largest_eigenvec_ind_c, ~] = find(eigenval == max(max(eigenval)));
largest_eigenvec = eigenvec(:, largest_eigenvec_ind_c);

% Get the largest eigenvalue
largest_eigenval = max(max(eigenval));

% Get the smallest eigenvector and eigenvalue
if(largest_eigenvec_ind_c == 1)
    smallest_eigenval = max(eigenval(:,2));
    smallest_eigenvec = eigenvec(:,2);
else
    smallest_eigenval = max(eigenval(:,1));
    smallest_eigenvec = eigenvec(1,:);
end

% Calculate the angle between the x-axis and the largest eigenvector
angle = atan2(largest_eigenvec(2), largest_eigenvec(1));

%% This angle is between -pi and pi.
% Let's shift it such that the angle is between 0 and 2pi
if(angle < 0)
    angle = angle + 2*pi;
end

% Get the coordinates of the data mean
avg = mean(data);

% Get the  confidence interval error ellipse
if (ci<0.95)&&(ci>=1)
    return
else
    if ci<0.975
        chisquare_val = 2.4477;
    else
        if ci<0.99
            chisquare_val = 2.7162;
        else
            if ci<0.995
                chisquare_val = 3.0348;
            else
                chisquare_val = 3.2553;
            end
        end
    end
end
theta_grid = linspace(0,2*pi);
X0 = avg(1); Y0 = avg(2);
a = chisquare_val*sqrt(largest_eigenval);
b = chisquare_val*sqrt(smallest_eigenval);

% Define a rotation matrix
R = [ cos(angle) sin(angle); -sin(angle) cos(angle) ];

% the ellipse in x and y coordinates 
% ellipse_x_r  = a*cos( theta_grid );
% ellipse_y_r  = b*sin( theta_grid );

% let's rotate the ellipse to some angle phi
% r_ellipse = [ellipse_x_r,ellipse_y_r] * R;

% Determine if the ellipse is exceeded
k = 1; singular = [];
for i = 1:length(data)
    coordinate = [data(i,1)-X0,data(i,2)-Y0]/R;
    if (coordinate(1)/a)^2 + (coordinate(2)/b)^2 > 1
        singular(k,:) = [i,data(i,1),data(i,2)];
        k = k+1;
    end
end
if isempty(singular)
    return;
end
% Draw the error ellipse
% figure
% plot(r_ellipse(:,1) + X0,r_ellipse(:,2) + Y0,'r-')
% hold on;
% plot(singular(:,2),singular(:,3),'.r');hold on;

% % Plot the original data
% plot(data(:,1), data(:,2), '.');
% mindata = min(data); maxdata = max(data);
% xlim([mindata(1), maxdata(1)]); ylim([mindata(2), maxdata(2)]);
% hold on;

% % Plot the eigenvectors
% quiver(X0, Y0, largest_eigenvec(1)*sqrt(largest_eigenval), largest_eigenvec(2)*sqrt(largest_eigenval), '-m', 'LineWidth',2);
% quiver(X0, Y0, smallest_eigenvec(1)*sqrt(smallest_eigenval), smallest_eigenvec(2)*sqrt(smallest_eigenval), '-g', 'LineWidth',2);
% hold on;

% % Set the axis labels
% hXLabel = xlabel('x');
% hYLabel = ylabel('y');
end
