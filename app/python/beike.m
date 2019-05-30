function [result] = beike(sourcefile,resultfile)

    result = sourcefile
    fid = fopen(resultfile, 'w');
	fprintf(fid,result);
	fclose(fid);
	result = 3

end