%%%%%%%%%%%%%%%%%%%%%%%%%
% �жϴ���״̬
% 2018-09-05
%%%%%%%%%%%%%%%%%%%%%%%%%
function pass = BALL_Z(sensor_r,sensor_l,gps)
if (nargin < 3)
    error('Input error');
end
%% �ж������
R_J = Touch(sensor_r,25,1000,100,26); % �ж��ҽŴ���
L_J = Touch(sensor_l,25,1000,100,26); % �ж��ҽŴ���
[R,~] = size(R_J); [L,~] = size(L_J);
if R > L
    [output,n] = Touch(sensor_r,3,100,100,4); 
else
    [output,n] = Touch(sensor_r,35,2000,100,36); 
end
% switch adept
%     case 3
%         [output,n] = Touch(sensor_r,3,100,100,4); 
%     case 2
%         [output,n] = Touch(sensor_r,10,300,100,11); 
%     case 1
%         [output,n] = Touch(sensor_r,15,500,100,16);
%     case 0
%         [output,n] = Touch(sensor_r,30,2000,100,36); 
% end
%% RIGHT
% �ж���û�д���
if  ~isempty(output)
    [z,~] = size(output); pass_r_l = []; pass_r_s = []; pass_r_t = [];
    if  isempty(gps)
        gps = zeros(n,2);
%     else
%         p = length(lat); P = length(sensor_r);
%         lat = RBF_resample(lat,10,ceil(10*P/p));
%         lon = RBF_resample(lon,10,ceil(10*P/p));
    end
    longpass3 = Long_pass(output,0.25,10,6,100,n); % �жϳ���
    shortpass3 = Long_pass(output,0.01,5,3,100,n); % �ж϶̴�
    if ~isempty(longpass3)
        [m,~] = size(longpass3);
        for i = 1:m
            [time,J,W] = Location(sensor_r(longpass3(i,1),:),gps);
            pass_r_l(i,:) = [1 1 time longpass3(i,2) J W longpass3(i,3)];
        end
        % ȥ���̴��еĳ���
        k = 1; i = 1;[Z,~] = size(shortpass3);
        while i <= Z
            if sum(longpass3(:,1) ~= shortpass3(i,1)) == length(longpass3(:,1))
                [time,J,W] = Location(sensor_r(shortpass3(i,1),:),gps);
                pass_r_s(k,:) = [1 2 time shortpass3(i,2) J W shortpass3(i,3)];
                k = k+1;
            end
            i = i+1;
        end
        % ȥ�������еĶ̴�
        k = 1; i = 1;
        while i <= z
            if sum(shortpass3(:,1) ~= output(i,1)) == length(shortpass3(:,1))
                [time,J,W] = Location(sensor_r(output(i,1),:),gps);
                pass_r_t(k,:) = [1 3 time output(i,2) J W output(i,3)];
                k = k+1;
            end
            i = i+1;
        end        
    else
        if ~isempty(shortpass3) % �ж��Ƿ��ж̴�
            [m,~] = size(shortpass3); 
            for i = 1:m
                [time,J,W] = Location(sensor_r(shortpass3(i,1),:),gps);
                pass_r_s(i,:) = [1 2 time shortpass3(i,2) J W shortpass3(i,3)];
            end
            % ȥ�������еĶ̴�
            k = 1; i = 1;
            while i <= z
                if sum(shortpass3(:,1) ~= output(i,1)) == length(shortpass3(:,1))
                    [time,J,W] = Location(sensor_r(output(i,1),:),gps);
                    pass_r_t(k,:) = [1 3 time output(i,2) J W output(i,3)];
                    k = k+1;
                end
                i = i+1;
            end                
        else
              for i = 1:z
                  [time,J,W] = Location(sensor_r(output(i,1),:),gps);
                  pass_r_t(i,:) = [1 3 time output(i,2) J W output(i,3)];
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
    [output,n] = Touch(sensor_l,35,2000,100,36);
end
% switch adept
%     case 0
%         [output,n] = Touch(sensor_l,3,100,100,4);
%     case 1
%         [output,n] = Touch(sensor_l,15,300,100,16); 
%     case 2
%         [output,n] = Touch(sensor_l,25,1000,100,26); 
%     case 3
%         [output,n] = Touch(sensor_l,30,2000,100,36);
% end
% �ж���û�д���
if  ~isempty(output)
    [z,~] = size(output); pass_l_l = []; pass_l_s = []; pass_l_t = [];
    if  isempty(gps)
        gps = zeros(n,2);
%     else
%         p = length(lat); P = length(sensor_l);
%         lat = RBF_resample(lat,10,ceil(10*P/p));
%         lon = RBF_resample(lon,10,ceil(10*P/p));
    end
    longpass3 = Long_pass(output,0.25,10,6,100,n); % �жϳ���
    shortpass3 = Long_pass(output,0.01,5,3,100,n); % �ж϶̴�
    if ~isempty(longpass3)
        [m,~] = size(longpass3);
        for i = 1:m
            [time,J,W] = Location(sensor_l(longpass3(i,1),:),gps);
            pass_l_l(i,:) = [0 1 time longpass3(i,2) J W longpass3(i,3)];
        end
        % ȥ���̴��еĳ���
        k = 1; i = 1;[Z,~] = size(shortpass3);
        while i <= Z
            if sum(longpass3(:,1) ~= shortpass3(i,1)) == length(longpass3(:,1))
                [time,J,W] = Location(sensor_l(shortpass3(i,1),:),gps);
                pass_l_s(k,:) = [0 2 time shortpass3(i,2) J W shortpass3(i,3)];
                k = k+1;
            end
            i = i+1;
        end
        % ȥ�������еĶ̴�
        k = 1; i = 1;
        while i <= z
            if sum(shortpass3(:,1) ~= output(i,1)) == length(shortpass3(:,1))
                [time,J,W] = Location(sensor_l(output(i,1),:),gps);
                pass_l_t(k,:) = [0 3 time output(i,2) J W output(i,3)];
                k = k+1;
            end
            i = i+1;
        end        
    else
        if ~isempty(shortpass3) % �ж��Ƿ��ж̴�
            [m,~] = size(shortpass3); 
            for i = 1:m
                [time,J,W] = Location(sensor_l(shortpass3(i,1),:),gps);
                pass_l_s(i,:) = [0 2 time shortpass3(i,2) J W shortpass3(i,3)];
            end
            % ȥ�������еĶ̴�
            k = 1; i = 1;
            while i <= z
                if sum(shortpass3(:,1) ~= output(i,1)) == length(shortpass3(:,1))
                    [time,J,W] = Location(sensor_l(output(i,1),:),gps);
                    pass_l_t(k,:) = [0 3 time output(i,2) J W output(i,3)];
                    k = k+1;
                end
                i = i+1;
            end                
        else
              for i = 1:z
                  [time,J,W] = Location(sensor_l(output(i,1),:),gps);
                  pass_l_t(i,:) = [0 3 time output(i,2) J W output(i,3)];
              end  
        end
    end
    pass_l = [pass_l_l;pass_l_s;pass_l_t];
else
    pass_l = [];
end
pass = [pass_r;pass_l];
end


