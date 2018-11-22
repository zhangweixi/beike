var arguments 	=  process.argv.splice(2);
var gcoord 		= require('../../public/node_modules/gcoord');
var fs 			= require('fs');

if(arguments.length < 2){

	console.log('命令形式: node gps.js data.txt result.txt');

	return;
}

var sourceFile = arguments[0];
var resultFile = arguments[1];







var data = fs.readFileSync(sourceFile);
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

for(var zeroKey of zeroKeys){

	data[zeroKey] = [0,0];
}

var gpsString = "";

for(var gps of data){

	gpsString  = gpsString +  gps[1] + " " + gps[0] + "\n";

}

fs.writeFile(resultFile,gpsString,function(){

	console.log('ok');
});







	

