
//var arguments 	=  process.argv.splice(2);
var argv 		= require('../../public/node_modules/yargs').argv;
var gcoord 		= require('../../public/node_modules/gcoord');
var fs 			= require('fs');



if(typeof(argv.outtype) == 'undefined'){

	console.log('need input a output type:--outtype file ,--outtype str');

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


	var zeroKeys = [];	
	for(var i in data){

		var d 	= data[i].split(" ");
		var lon = d[1] * 1;
		var lat = d[0] * 1;

		data[i] = [lon,lat];

		if(lon == 0)
		{
			zeroKeys.push(i);
		}
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

data = result.coordinates;

if(outType == 'str'){

	console.log(data[0]);
	return;
}


//以下以文件的形式保存
for(var zeroKey of zeroKeys){

	data[zeroKey] = [0,0];
}

var gpsString = "";

for(var gps of data){

	gpsString  = gpsString +  gps[1] + " " + gps[0] + "\n";

}

fs.writeFile(outputFile,gpsString,function(){

	console.log('1');
});







	

