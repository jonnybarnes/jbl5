/// These first functions are only run on the new note page
function success(position) {
  var latlng = position.coords.latitude + ',' + position.coords.longitude;

  var geoDiv = document.querySelectorAll('.note-ui')[0];

  var labelLocate = document.createElement('label');
  labelLocate.setAttribute('for', 'confirmlocation');
  labelLocate.setAttribute('accesskey', 'c');
  var labelLocateText = document.createTextNode('Confirm location:');
  labelLocate.appendChild(labelLocateText);
  var locateBox = document.createElement('input');
  locateBox.setAttribute('type', 'checkbox');
  locateBox.setAttribute('name', 'confirmlocation');
  locateBox.setAttribute('id', 'confirmlocation');
  locateBox.setAttribute('value', '1');
  locateBox.setAttribute('checked', 'checked');
  var br = document.createElement('br');

  geoDiv.appendChild(labelLocate);
  geoDiv.appendChild(locateBox);
  geoDiv.appendChild(br);


  var combinedLatLng = position.coords.latitude + ', ' + position.coords.longitude;
  var location = document.createElement('input');
  location.setAttribute('type', 'hidden');
  location.setAttribute('name', 'location');
  location.setAttribute('value', combinedLatLng);
  geoDiv.appendChild(location);

  var labelAddress = document.createElement('label');
  labelAddress.setAttribute('for', 'address');
  labelAddress.setAttribute('accesskey', 'a');
  var labelAddressText = document.createTextNode("Address:");
  labelAddress.appendChild(labelAddressText);
  geoDiv.appendChild(labelAddress);

  var labelInput = document.createElement('input');
  labelInput.setAttribute('type', 'text');
  labelInput.setAttribute('name', 'address');
  labelInput.setAttribute('id', 'address');
  geoDiv.appendChild(labelInput);

  var newMap = document.createElement('div');
  newMap.setAttribute('class', 'map');
  newMap.dataset.latitude = position.coords.latitude;
  newMap.dataset.longitude = position.coords.longitude;

  geoDiv.appendChild(newMap);
  makeMap(newMap, true);
}

function error(msg) {
	var s = document.querySelectorAll('.geo-status');
	s.innerHTML = typeof msg == 'string' ? msg : 'failed';
	s.className = 'fail';
}

var locatebtn = document.querySelector('#locate');
if(locatebtn) {
  locatebtn.addEventListener('click', function() {
    if(navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(success, error);
    } else {
      error('not supported');
    }
  });
}

//This code runs on page load and looks for <div class="map">, then adds map
var mapDivs = document.querySelectorAll('.map');
for(var i = 0; i < mapDivs.length; i++) {
  var mapDiv = mapDivs[i];
  makeMap(mapDiv, false);
}

//The actual make map function, run on new note page AND notes pages
function makeMap(div, newnote) {
  var latitude = div.dataset.latitude;
  var longitude  = div.dataset.longitude;
  L.mapbox.accessToken = 'pk.eyJ1Ijoiam9ubnliYXJuZXMiLCJhIjoiVlpndW1EYyJ9.aP9fxAqLKh7lj0LpFh5k1w';
  var map = L.mapbox.map(div, 'jonnybarnes.gnoihnim')
    .setView([latitude, longitude], 15)
    .addLayer(L.mapbox.tileLayer('jonnybarnes.gnoihnim', {
      detectRetina: true,
    }));
  if(newnote == true) {
    var marker = L.marker([latitude, longitude], {
        draggable: true
    }).addTo(map);
    marker.on('dragend', function() {
      var ll = marker.getLatLng();
      var combined = ll.lat + ', ' + ll.lng;
      console.log(combined);
      var location = document.getElementsByName('location')[0];
      location.value = combined;
    });
  } else {
    var marker = L.marker([latitude, longitude]).addTo(map);
  }

  map.scrollWheelZoom.disable();

}
