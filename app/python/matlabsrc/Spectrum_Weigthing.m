%%%%%%%%%%%%%%%%%%%%%%%%%%%%
% Spectrum_Weigthing
% 2018-08-19
%%%%%%%%%%%%%%%%%%%%%%%%%%%%
function [X_DI,Output_DI] = Spectrum_Weigthing(X,Samp,K,DI)
if (nargin < 4)
    DI = 1; 
end
if (nargin < 3)
    K = 2; 
end
if (nargin < 2)
	error('Must input a vector X')
end
fs = Samp;
dt = 1/fs;
t = dt:dt:length(X)/fs;

N=length(X);
ff= (-4*pi^K).*(((1:N/2).*fs)./N).^K;
H = fft(X);
if DI == 1
    Hkk = H(1:N/2).*2.*ff';
else
    Hkk = H(1:N/2).*2./ff';
end
an = ifft(Hkk,N);
X_DI = real(an)';
Output_DI = mapminmax(X_DI);
figure
plot(t,X_DI);
xlabel('Ê±¼ä/s'); ylabel('AA');
end