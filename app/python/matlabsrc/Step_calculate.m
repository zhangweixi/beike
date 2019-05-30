%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% 计算步数和步频
% 2018-10-25
%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function Step_result =  Step_calculate(sensor_r,sensor_l,fs)
%% 右脚
output_r = step(sensor_r,fs); time_r = length(sensor_r)/fs; 
%% 左脚
output_l = step(sensor_l,fs); time_l = length(sensor_l)/fs;
%% 判断两脚的数据
if ~isempty(output_r)
    if ~isempty(output_l)
        Y = sort([output_r(:,1);output_l(:,1)],1,'ascend');
    else
        Y = sort(output_r(:,1),1,'ascend');
    end
else
    if ~isempty(output_l)
        Y = sort(output_l(:,1),1,'ascend');
    else
        Y = [];
    end
end
%% 结果
if isempty(Y)
    Step_result = [];
    return;
end
X = 1./diff(Y,1);
S = length(output_r)+length(output_l);
Z = length(output_r)/time_r+length(output_l)/time_l;
Step_result = [S,Z,X'];
end