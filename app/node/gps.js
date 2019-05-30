/*
* 调用命令
* 单个GPS node gps.js --outtype=str  --lat=31.344 --lon=121.344
* 文件GPS node gps.js --outtype=file --input=gps-src.txt --output=reuslt.txt
*
* */
//var arguments 	=  process.argv.splice(2);
var argv 		= require('../../public/node_modules/yargs').argv;
var gcoord 		= require('../../public/node_modules/gcoord');
var fs 			= require('fs');
var path 		= require('path');


if(typeof(argv.h) != 'undefined'){
	
	console.log('single gps: node gps.js --outtype=str --lat=31.344  --lon=121.34323');
	console.log('file   gps: node gps.js --outtype=file --input=inputfile --output=outfile');
	return;
}

if(typeof(argv.outtype) == 'undefined'){

	console.log('need input a output type:--outtype file or --outtype str');
	return;
}

var outType = argv.outtype;

if(outType == 'file'){  //文件的形式保存

	var inputFile 	= argv.input;
	var outputFile 	= argv.output;

	if(typeof(inputFile) == 'undefined' || typeof(outputFile) == 'undefined'){

		console.log('need input file or output file');

		return;
	}


	var data = fs.readFileSync(inputFile);
		data = data.toString().split("\n");

		data.splice(data.length-1,1);

	var originGps= []; //原始GPS

	for(var i in data){

		var d 	= data[i].split(" ");
		var lon = d[1] * 1;
		var lat = d[0] * 1;

		data[i] = [lon,lat];
		originGps.push(d);
	}

}else if(outType == 'str'){ //字符串的形式返回


	var lat = argv.lat;
	var lon = argv.lon;

	if(typeof(lat) == 'undefined' || typeof(lon) == 'undefined'){

		console.log('need lat or lon');

		return;
	}

	var data = [[lon,lat]];
}


var geojson = {
	type:'LineString',
	coordinates:data
};
	
var result = gcoord.transform(
  	geojson,    				// 经纬度坐标
  	gcoord.WGS84,             // 当前坐标系
  	gcoord.BD09               // 目标坐标系
);

var resultGps = result.coordinates;

if(outType == 'str'){

	console.log(resultGps[0]);
	return;
}

var gpsString = "";


for(var key in originGps){

	var gps 	= originGps[key];

	if(gps[0] != 0 || gps[1] != 0)
	{
        gps[0] = resultGps[key][1];
        gps[1] = resultGps[key][0];
	}

	gpsString  = gpsString +  gps.join(" ") + "\n";
}

fs.writeFile(outputFile,gpsString,function(){

	console.log('1');
});







	

