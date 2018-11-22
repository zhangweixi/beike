%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% ���㲽���Ͳ�Ƶ
% 2018-10-25
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function Step_result =  Step_calculate(sensor_r,sensor_l,fs)
%% �ҽ�
output_r = step(sensor_r,fs); time_r = length(sensor_r)/fs; 
%% ���
output_l = step(sensor_l,fs); time_l = length(sensor_l)/fs;
%% ���
Y = sort([output_r(:,1);output_l(:,1)],1,'ascend');
if isempty(Y)
    Step_result = [];
    return;
end
X = 1./diff(Y,1);
S = length(output_r)+length(output_l);
Z = length(output_r)/time_r+length(output_l)/time_l;
Step_result = [S,Z,X'];
end