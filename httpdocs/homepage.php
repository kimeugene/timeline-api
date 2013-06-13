<?php

?>
<html>
 <head>
  <title>Timeline for iOS</title>
  <link href='http://fonts.googleapis.com/css?family=Alif' rel='stylesheet' type='text/css'>
  <link href='assets/css/reset.css' rel='stylesheet' type='text/css'>
  <link href='assets/css/main.css'  rel='stylesheet' type='text/css'>
  <script src='assets/js/jquery-2.0.2.min.js' type='text/javascript'></script>
  <script>
   $(document).ready(function() {
     $('.header').animate({ opacity: 1 }, 1500);
     $('.main-map').animate({ opacity: 0.3 }, 1500, function() {
       $('.main-screenshot,.download').animate({ opacity: 1 }, 500);
     });
   });
  </script>
 </head>
 <body>
  <div class="header">
    <div class="container">
      <div class="logo">Timeline<span style="color:#ccc; font-size: 24px;"> for iPhone</span></div>
      <div class="promo">
      </div>
    </div>
  </div>

  <div class="main-map" style="border-top: 1px solid #eee; opacity: 0.0; border-bottom: 4px solid #aaa;">
    <iframe width='100%' height='380' frameBorder='0' src='http://a.tiles.mapbox.com/v3/fitz.map-vu2u1c76.html#16/40.71909999999999/-73.99270000000001'></iframe>
  </div>


  <div class="content">
    <div class="container">
 
      <div class="main-screenshot">
      </div>
      <div class="main-promo">
        <div class="button download" style="margin-top: 310px;">
          <div class="text">Try Timeline for iPhone</div>
        </div>
      </div>
      <br clear="all" />

    </div>
  </div>

 </body>
</html>