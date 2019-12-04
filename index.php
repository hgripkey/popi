<!doctype html>


<html lang="en">
  <head>
    <link rel="stylesheet" href="https://cdn.rawgit.com/openlayers/openlayers.github.io/master/en/v5.3.0/css/ol.css" type="text/css">
    <style>
      html, body, *{
	 margin: 0;
         border-radius: 5px;
      }
      #map {
        margin: none;
        grid-column: 2 / span 2;
	grid-row: 1 / span 2;

      }
      #map_head {
        margin: auto;
        position: relative;
        text-align: center;
      }
      #map_holder {
        display: grid;
        grid-template-columns: 25% auto;
        grid-template-rows: minmax(600px,600px) minmax(250px, 250px);
        border-width: 2px;
        border-color: grey;
	border-style: solid;
	min-width: 0;
	min-height: 0;

      }
      #popup {
        grid-column: 1;
        grid-row: 1 / span 1;
        border-width: 2px;
        border-color: black;
        border-style: solid;
	overflow-y: scroll;
	min-width: 0;

      }
      #submission {
        grid-column: 1;
        grid-row: 2;
        border-width: 2px;
        border-color: black;
        border-style: solid;
      }
      #footer{
        margin: auto;
        text-align: center;
      }
    </style>
    <!--Script include for OpenLayers -->
    <script src="https://cdn.rawgit.com/openlayers/openlayers.github.io/master/en/v5.3.0/build/ol.js"></script>
    <title>OpenLayers Popi Test Page</title>
  </head>
  <body>

    <h2 id="map_head">Sample Map Query</h2>
    <div id="map_holder">

      <div id="popup" style="font-size: small;"></div>
        <script type="text/javascript">

        let divInfo = document.getElementById('popup');
        let table = document.createElement('table');
        table.setAttribute('border','1');
        table.setAttribute('width','100%');
        table.style.borderCollapse = 'collapse';
        let rowCount = 0;
        let row = table.insertRow(0);
        let headers = ['SampleID','Latitude','Longitude','Village/City','Country','Collection Date'];
        for (let i=0;i<6;i++) {
          let cell = row.insertCell(i);
          let text = document.createTextNode(headers[i]);
          cell.setAttribute('align','center');
          cell.style.fontWeight = 'bold';
          cell.appendChild(text);

	}
        divInfo.appendChild(table);

	</script>
      <div id="submission">


   <textarea name='samples' form='samplesform' rows='10' cols='15' style='width:95%;'><?php
if ( isset($_POST['submits'])){
	$points = explode("\n", str_replace("\r", "", $_POST['samples']));
	foreach ($points as $item) {
		if ($item != "") { echo "$item\n"; }
	}
}
		?></textarea><br>

	  <form action="index.php" method="post" id="samplesform">
            <input type="submit" name="submits" value="submit">
	  </form>
      </div>
      <div id="map"></div>

    </div>
    <div id="footer"></div>

    <script type="text/javascript">




      //Create a new map object
      const map = new ol.Map({
        target: 'map',
        layers: [
          new ol.layer.Tile({
            source: new ol.source.OSM()
          })
        ],
        view: new ol.View({
          center: ol.proj.fromLonLat([-8, 11]),
          zoom: 3,
          minZoom:3,
          maxZoom:16

        })
      });
      //List of points, will need to modify this for specific samples, include ID and country at least

        </script>
      <?php
      //Do the query here and create the markers

        require('../includes.php');
        $dbconnection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if(!$dbconnection) {
          echo "Error: DataBase connection failed" . PHP_EOL;
        }

	if (isset($_POST['submits'])) {
	  #Need to create a list of ? and 's' equal to the number of samples to prepare the statement and explode out the samples
	  $points = explode("\n", str_replace("\r", "", $_POST['samples']));
	  $question_marks = str_repeat('?, ', count($points));
	  $question_marks = preg_replace('/, $/', '', $question_marks);
          $types = str_repeat('s', count($points));
          $stmt = $dbconnection->prepare("select Sample.banked_sample_id, Collection_site.latitude, Collection_site.longitude, Sample.collection_date, Collection_site.name as village, Collection_site.country from Sample LEFT JOIN Collection ON Collection.id = Sample.collection_id LEFT JOIN Collection_site ON Collection.village = Collection_site.id where Sample.banked_sample_id IN ($question_marks);");
          $stmt->bind_param($types, ...$points);
          $stmt->execute();
          $result=$stmt->get_result();

	  //Create a javascript array of objects to hold query information
	  echo "<script type='text/javascript'>";
          echo "let markers = [";
          $count = 0; //Count used to prevent comma at last entry
	  $numrows = $result->num_rows;
	  //Loop through the results and add them to the object
	  while ($row = $result->fetch_assoc()) {
		  if (is_float($row['longitude'])  && is_float($row['latitude'])) {
	            echo "{lng: ".$row['longitude'].", lat: ".$row['latitude'].", id: '".htmlspecialchars($row['banked_sample_id'])."', collectionDate: '".$row['collection_date']."', village: '".addslashes($row['village'])."', country: '".htmlspecialchars($row['country'])."'}";

           	 	if ($count != $numrows-1) {
             	 		echo ",";
			}
	  }else{console.log("No latitude or longitude for sample: ".$row['banked_sample_id']);}
		$count = $count + 1;
		echo "\n";
          }
          echo "];";
          echo "</script>";
	}


      ?>

      <script type="text/javascript">



      let featuresVis = [];
      let featuresInvis = [];
      //For each marker add the point onto the vector layer point
      for(let i = 0; i<markers.length; i++){

	      let item = markers[i];
        let id = item.id
      	let lng = item.lng;
        let lat = item.lat;
      	let village = item.village;
      	let country = item.country;
      	let collectionDate = item.collectionDate;

        let iconFeatureInvis = new ol.Feature({
          geometry: new ol.geom.Point(ol.proj.transform([lng, lat],  'EPSG:4326', 'EPSG:3857'))
      	});
        let iconFeatureVis = new ol.Feature({
          geometry: new ol.geom.Point(ol.proj.transform([lng, lat],  'EPSG:4326', 'EPSG:3857'))
      	});
      	//Set Key value pairs to hold printing information
        iconFeatureVis.set('Hidden',false);
        iconFeatureInvis.set('Hidden',true);
      	iconFeatureInvis.set('SampleId',id);
      	iconFeatureInvis.set('lat',lat);
      	iconFeatureInvis.set('lng',lng);
      	iconFeatureInvis.set('village',village);
      	iconFeatureInvis.set('country',country);
      	iconFeatureInvis.set('collectionDate',collectionDate);
              //let iconStyle = new ol.style.Style({
              //  image: new ol.style.Icon(({
              //    src: "http://cdn.mapmarker.io/api/v1/pin?text=P&size=50&hoffset=1"
              //  }))
              //});
      	//iconFeature.setStyle(iconStyle);
      	iconFeatureVis.setStyle(new ol.style.Style({
                  stroke: new ol.style.Stroke({
                    color: '#FA3703',
                    width: 3
                  }),
                  image: new ol.style.Circle({
                    radius: 5,
                    stroke: new ol.style.Stroke({
                      color: '#FA3703',
                      width: 3
                    })
                  })
          }));

              featuresVis.push(iconFeatureVis);
              featuresInvis.push(iconFeatureInvis);
        }
        const vectorSourceVis = new ol.source.Vector({
          features: featuresVis
        });
        const vectorSourceInvis = new ol.source.Vector({
          features: featuresInvis
        });

        const vectorLayerVis = new ol.layer.Vector({
            source: vectorSourceVis,
            declutter: true
        });
        const vectorLayerInvis = new ol.layer.Vector({
          source: vectorSourceInvis
        });
        vectorLayerInvis.setOpacity(0);
        map.addLayer(vectorLayerVis);
        map.addLayer(vectorLayerInvis);

        //Set a pixel array to allow default values
        let highlightedFeatures = [];

        //Map onclick function to select points
        map.on('click',function(e) {

      	//Set variable to contain the information div and remove previous information
      	let divInfo = document.getElementById('popup');
      	while(divInfo.firstChild){
      	    divInfo.removeChild(divInfo.firstChild);
      	}

      	//Set the default style of all points previously in the array
              highlightedFeatures.forEach(f => f.setStyle(new ol.style.Style({
                  stroke: new ol.style.Stroke({
                    color: '#FA3703',
                    width: 3
                  }),
                  image: new ol.style.Circle({
                    radius: 5,
                    stroke: new ol.style.Stroke({
                      color: '#FA3703',
                      width: 3
                    })
                  })
              })));
	highlightedFeatures = [];

	//Create the information table element
	let table = document.createElement('table');
	table.setAttribute('border','1');
	table.setAttribute('width','100%');
	table.style.borderCollapse = 'collapse';
	let rowCount = 0; 		//Row increment
	let row = table.insertRow(0);

	//Create the header for the table
	let headers = ['SampleID','Latitude','Longitude','Village/City','Country','Collection Date'];
	for (let i=0;i<6;i++) {
	  let cell = row.insertCell(i);
          let text = document.createTextNode(headers[i]);
	  cell.setAttribute('align','center');
	  cell.style.fontWeight = 'bold';
	  cell.appendChild(text);

	}
	rowCount++; //Increment row

	//Iterate through each element clicked at the pixel, style and add rows to table
	map.forEachFeatureAtPixel(e.pixel, f => {
   if (f.get('Hidden'))
          f.setStyle(new ol.style.Style({
            stroke: new ol.style.Stroke({
              color: '#0d47a1',
              width: 3
            }),
            image: new ol.style.Circle({
              radius: 4,
              stroke: new ol.style.Stroke({
                color: '#0d47a1',
                width: 3
              })
            })
	}));
        if( rowCount <= 50 ){
          //Create the row for the table
	  let row = table.insertRow(rowCount);
	  let varArray = [f.get('SampleId'),f.get('lat'),f.get('lng'),f.get('village'),f.get('country'),f.get('collectionDate')];
	  for (let i=0;i<varArray.length;i++){
	    let cell = row.insertCell(i);
	    cell.setAttribute('align','center');
	    //Create an achor tag if it is the first elemen (sample ID)
	    if (i === 0){
              let anchor = document.createElement('a')
              let url = "https://popi.ucdavis.edu/PopulationData/Workbench/DataViews/indiv2.php?banked_id="  + varArray[0];
	      anchor.setAttribute('href', "javascript:window.open('"+url+"', '"+varArray[0]+"','width=800,height=800')");
              anchor.innerText = varArray[0];
	      cell.appendChild(anchor);
	    }else {
	     let text = document.createTextNode(varArray[i]);
	     cell.appendChild(text);
	    }
	  }
	  if(rowCount % 2 === 1){
            row.style.backgroundColor = '#dddddd';
	  }
	}
	else if (rowCount === 51){
	   let row = table.insertRow(rowCount);
           let cell = row.insertCell(0);
           cell.setAttribute('align','center');
	   let text = document.createTextNode("<50 samples present. List truncated");
	   cell.appendChild(text);

           if(rowCount % 2 === 1){
            row.style.backgroundColor = '#dddddd';
           }

	}

	  //Increment rowcount to create a new row next iteration
	  rowCount++;

	  //push current points to previous points
          highlightedFeatures.push(f);
	}});

	//Append the table to the popup window
	divInfo.appendChild(table);

});

      //To get zoom level use map.getView().getZoom();
    </script>
  </body>
</html>
