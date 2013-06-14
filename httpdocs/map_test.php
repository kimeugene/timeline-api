<!DOCTYPE html>
<html>
  <head>
    <title>Timeline Map</title>
    <meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <style type="text/css">
      html { height: 100% }
      body { height: 100%; margin: 0; padding: 0; font-family: 'Lucida Grande', 'Arial'; font-size: 12px; }
      #map_canvas { height: 100% }
    </style>
    <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB3X-jzV35dR45FDEq2GC9lgF19RfI3Jm4&sensor=false"></script>
    <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    <script type="text/javascript">

$(document).ready(function() {
    // Map initialization
    var map;
    var overlays = [];
    var markers = [];
    var infowindows = [];

    function initialize() {
        var mapOptions = {
          center: new google.maps.LatLng(40.763028, -74.024498),
          zoom: 13,
          mapTypeId: google.maps.MapTypeId.ROADMAP
        };
        map = new google.maps.Map(document.getElementById("map_canvas"), mapOptions);
        $('#zoom').val(mapOptions.zoom);
    }

    function refreshOnZoom() {
        map.event.addListener(marker, 'zoom_changed', function(){});
    }

    function listenMarker (marker, infowindow){
        // so marker is associated with the closure created for the listenMarker function call
        google.maps.event.addListener(marker, 'click', function() {
            infowindow.open(map,marker);
        });
    }

    // Mobile Device support
    function detectBrowser() {
        var useragent = navigator.userAgent;
        var mapdiv = document.getElementById("map_canvas");

        if (useragent.indexOf('iPhone') != -1 || useragent.indexOf('Android') != -1 ) {
            mapdiv.style.width = '100%';
            mapdiv.style.height = '100%';
        } 
    }

    function update(){
        email = $('#email').val();
        date = $('#date').val();
        datapoints = $('#datapoints').val();

        if(email.length == 0) {
            alert('Please enter the email address key for DynamoDB and try again.');
            return;
        }

        $.getJSON('/get/'+email+'/'+date+'/'+datapoints, function(response) {
            for(i=0; i<overlays.length; i++) {
              overlays[i].setMap(null);
            }

            if (markers) {
                for (i in markers) {
                  markers[i].setMap(null);
                }
            }

            overlays = [];
            markers = [];

            var flightPlanCoordinates = [];
            for(i=0; i<response.length; i++) {
                flightPlanCoordinates.push(new google.maps.LatLng(response[i][0], response[i][1]));
                var myLatlng = new google.maps.LatLng(response[i][0], response[i][1]);
                var mapOptions = {
                    zoom: 4,
                    center: myLatlng,
                    mapTypeId: google.maps.MapTypeId.ROADMAP
                }
                var contentString = response[i][2];
                var infowindow = new google.maps.InfoWindow({
                    content: contentString
                });
                infowindows.push(infowindow);
                var marker = new google.maps.Marker({
                    position: myLatlng,
                    map: map,
                    title: ""
                });

                listenMarker(marker, infowindow);
                markers.push(marker);
            }

            var flightPath = new google.maps.Polyline({
                path: flightPlanCoordinates,
                strokeColor: "#000",
                strokeOpacity: 0.5,
                strokeWeight: 4
            });
            flightPath.setMap(map);        
            overlays.push(flightPath);
        });        
        return false;
    }

    initialize();
    detectBrowser();

    // Click Handlers
    $('#fetch').click(update());
    $('#togglePanel').click(function() {
        $('#overlay').toggle();
    });

    // Click this in the beginning
    $('#fetch').click();
});
    </script>
  </head>
  <body>
    <div id="map_canvas" style="width:100%; height:100%"></div>
    <input type="button" id="togglePanel" value="Toggle Panel" style="width: 100px; z-index: 5; position: absolute; right: 127px; top: 4px; opacity: 1;" />

    <form>
    <div id="overlay" style="z-index:5; width: 200px;position: absolute; right: 5px; top: 40px; background-color: #fff; border: 1px solid #000; opacity: .75; padding: 10px; box-shadow: 3px 3px 5px #777; border-radius: 3px;" >
     <b>Timeline User ID:</b><br>
     <input type="text" id="email" style="width: 150px; height: 16px; margin-top: 5px; margin-bottom: 8px; padding: 5px;" value="pwnage" /><br>

     <div style="width: 100%; margin-top: 5px; margin-bottom: 8px;">
       <div style="width: 50%; float: left;">
         <b>Date:</b><br>
         <input type="text" id="date" style="width: 70px; height: 16px; margin-top: 5px; margin-bottom: 8px; padding: 5px;" value="<?=date('Y-m-d')?>" maxlength="10" /><br>     
       </div>
       <div style="width: 50%; float: left;">
         <b>Data Points:</b><br>
         <input type="text" id="datapoints" style="width: 40px; height: 16px; margin-top: 5px; margin-bottom: 8px; padding: 5px;" value="500" /><br>     
       </div>
     </div>

     <input type="hidden" id="zoom" value="" />
     <input type="submit" id="fetch" value="Fetch" style="width: 100%; margin-top: 3px;" />
    </div>
    </form>
  </body>
</html>
