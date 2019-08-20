/*  Functions for use with OpenLayers on popi
 *  Started:     8/16/2019
 *  Author:     Hans Gripkey
 */

//Function to plot all samples from a given array onto the current map. 
//Takes in an array and features object
//Modifies the features object with points added
function plotPointArray(arr,features) {
    for( let i=0;i<arr.length;i++ ){
        
        let item = arr[i];
        let lng = item.lng;
        let lat = item.lat;
        let sampleId = item.id;
        
        let iconFeature = new ol.Feature ({
            geometry: new ol.geom.Point(ol.proj.transform([lng, lat],  'EPSG:4326', 'EPSG:3857'));
        })
            
        features.push(iconFeature);
    }
}


//Function to modify all values clicked by the pointer
function pointClick(e) {
//Set the default style of all points previously in the array
    highlightedFeatures.forEach(f => f.setStyle(null));
    highlightedFeatures = [];

    map.forEachFeatureAtPixel(e.pixel, f => {

        f.setStyle(new ol.style.Style({
          stroke: new ol.style.Stroke({
            color: '#0d47a1',
            width: 2
          }),
          image: new ol.style.Circle({
            radius: 3,
            stroke: new ol.style.Stroke({
              color: '#0d47a1',
              width: 2
            })
          })
        }));
	
        //push current points to previous points
        highlightedFeatures.push(f);
	});
}

