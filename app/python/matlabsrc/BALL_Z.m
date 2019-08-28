%%%%%%%%%%%%%%%%%%%%%%%%%
% 判断触球状态
% 2018-09-05
%%%%%%%%%%%%%%%%%%%%%%%%%
function pass = BALL_Z(sensor_r,sensor_l,gps)
if (nargin < 3)
    error('Input error');
end
%% 判断踢球脚
R_J = Touch(sensor_r,25,1000,100,26); % 判断右脚触球
L_J = Touch(sensor_l,25,1000,100,26); % 判断右脚触球
[R,~] = size(R_J); [L,~] = size(L_J); pass_r = []; pass_l = [];
if R > L
    [output,n] = Touch(sensor_r,3,100,100,4); 
else
    [output,n] = Touch(sensor_r,55,3000,100,56); 
end
%% RIGHT
% 判断有没有触球
if  ~isempty(output)
    [z,~] = size(output); pass_r_l = []; pass_r_s = []; pass_r_t = [];
    if  isempty(gps)
        gps = zeros(n,2);
    end
    longpass3 = Long_pass(output,0.25,10,6,100,n); % 判断长传
    shortpass3 = Long_pass(output,0.01,5,3,100,n); % 判断短传
    if ~isempty(longpass3) % 判断有没有长传
        [m,~] = size(longpass3);
        for i = 1:m
            [time,J,W] = Location(sensor_r(longpass3(i,1),:),gps);
            pass_r_l(i,:) = [1 1 time longpass3(i,2) J W longpass3(i,5)];
        end
        if ~isempty(shortpass3) % 判断有没有短传
            % 去掉短传中的长传
            k = 1; i = 1;[Z,~] = size(shortpass3);
            while i <= Z
                 if sum(longpass3(:,1) ~= shortpass3(i,1)) == length(longpass3(:,1))
                    [time,J,W] = Location(sensor_r(shortpass3(i,1),:),gps);
                    pass_r_s(k,:) = [1 2 time shortpass3(i,2) J W shortpass3(i,5)];
                    k = k+1;
                 end
                 i = i+1;
            end
            % 去掉触球中的短传
            k = 1; i = 1;
            while i <= z
                if sum(shortpass3(:,1) ~= output(i,1)) == length(shortpass3(:,1))
                   [time,J,W] = Location(sensor_r(output(i,1),:),gps);
                   pass_r_t(k,:) = [1 3 time output(i,2) J W output(i,5)];
                   k = k+1;
                end
                i = i+1;
            end
        else
            % 去掉触球中的长传
            k = 1; i = 1;
            while i <= z
                if sum(longpass3(:,1) ~= output(i,1)) == length(longpass3(:,1))
                   [time,J,W] = Location(sensor_r(output(i,1),:),gps);
                   pass_r_t(k,:) = [1 3 time output(i,2) J W output(i,5)];
                   k = k+1;
                end
                i = i+1;
            end
        end
    else
        if ~isempty(shortpass3) % 判断是否有短传
            [m,~] = size(shortpass3); 
            for i = 1:m
                [time,J,W] = Location(sensor_r(shortpass3(i,1),:),gps);
                pass_r_s(i,:) = [1 2 time shortpass3(i,2) J W shortpass3(i,5)];
            end
            % 去掉触球中的短传
            k = 1; i = 1;
            while i <= z
                if sum(shortpass3(:,1) ~= output(i,1)) == length(shortpass3(:,1))
                    [time,J,W] = Location(sensor_r(output(i,1),:),gps);
                    pass_r_t(k,:) = [1 3 time output(i,2) J W output(i,5)];
                    k = k+1;
                end
                i = i+1;
            end                
        else
              for i = 1:z
                  [time,J,W] = Location(sensor_r(output(i,1),:),gps);
                  pass_r_t(i,:) = [1 3 time output(i,2) J W output(i,5)];
              end  
        end
    end
    pass_r = [pass_r_l;pass_r_s;pass_r_t];
else
    pass_r = [];
end
%% LEFT
if L > R
    [output,n] = Touch(sensor_l,3,100,100,4);
else
    [output,n] = Touch(sensor_l,55,3000,100,56);
end
% 判断有没有触球
if  ~isempty(output)
    [z,~] = size(output); pass_l_l = []; pass_l_s = []; pass_l_t = [];
    if  isempty(gps)
        gps = zeros(n,2);
    end
    longpass3 = Long_pass(output,0.25,10,6,100,n); % 判断长传
    shortpass3 = Long_pass(output,0.01,5,3,100,n); % 判断短传
    if ~isempty(longpass3)
        [m,~] = size(longpass3);
        for i = 1:m
            [time,J,W] = Location(sensor_l(longpass3(i,1),:),gps);
            pass_l_l(i,:) = [0 1 time longpass3(i,2) J W longpass3(i,5)];
        end
        if ~isempty(shortpass3) % 判断是否有短传
            % 去掉短传中的长传
            k = 1; i = 1;[Z,~] = size(shortpass3);
            while i <= Z
                if sum(longpass3(:,1) ~= shortpass3(i,1)) == length(longpass3(:,1))
                   [time,J,W] = Location(sensor_l(shortpass3(i,1),:),gps);
                   pass_l_s(k,:) = [0 2 time shortpass3(i,2) J W shortpass3(i,5)];
                   k = k+1;
                end
                i = i+1;
            end
            % 去掉触球中的短传
            k = 1; i = 1;
            while i <= z
                if sum(shortpass3(:,1) ~= output(i,1)) == length(shortpass3(:,1))
                    [time,J,W] = Location(sensor_l(output(i,1),:),gps);
                    pass_l_t(k,:) = [0 3 time output(i,2) J W output(i,5)];
                    k = k+1;
                end
                i = i+1;
            end
        else
            % 去掉触球中的长传
            k = 1; i = 1;
            while i <= z
                if sum(longpass3(:,1) ~= output(i,1)) == length(longpass3(:,1))
                    [time,J,W] = Location(sensor_l(output(i,1),:),gps);
                    pass_l_t(k,:) = [0 3 time output(i,2) J W output(i,5)];
                    k = k+1;
                end
                i = i+1;
            end    
        end       
    else
        if ~isempty(shortpass3) % 判断是否有短传
            [m,~] = size(shortpass3); 
            for i = 1:m
                [time,J,W] = Location(sensor_l(shortpass3(i,1),:),gps);
                pass_l_s(i,:) = [0 2 time shortpass3(i,2) J W shortpass3(i,5)];
            end
            % 去掉触球中的短传
            k = 1; i = 1;
            while i <= z
                if sum(shortpass3(:,1) ~= output(i,1)) == length(shortpass3(:,1))
                    [time,J,W] = Location(sensor_l(output(i,1),:),gps);
                    pass_l_t(k,:) = [0 3 time output(i,2) J W output(i,5)];
                    k = k+1;
                end
                i = i+1;
            end                
        else
              for i = 1:z
                  [time,J,W] = Location(sensor_l(output(i,1),:),gps);
                  pass_l_t(i,:) = [0 3 time output(i,2) J W output(i,5)];
              end  
        end
    end
    pass_l = [pass_l_l;pass_l_s;pass_l_t];
else
    pass_l = [];
end
pass = [pass_r;pass_l];
end
