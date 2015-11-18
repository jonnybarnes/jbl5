var button = document.querySelector('#locate');

if (button.addEventListener) {
    button.addEventListener('click', getLocation);
} else {
    button.attachEvent('onclick', getLocation);
}

function getLocation() {
    if ('geolocation' in navigator) {
        navigator.geolocation.getCurrentPosition(function (position) {
            addPlaces(position.coords.latitude, position.coords.longitude);
            //addMap(position.coords.latitude, position.coords.longitude);
        });
    } else {
        console.log('Geolocation unavaliable');
    }
}

function addPlaces(latitude, longitude) {
    fetch('/places/near/' + latitude + '/' + longitude, {
        method: 'get'
    }).then(function (response) {
        return response.json();
    }).then(function (j) {
        if (j.length > 0) {
            var i;
            var places = [];
            for (i = 0; i < j.length; ++i) {
                var latlng = parseLocation(j[i].location);
                var name = j[i].name;
                places.push([name, latlng[0], latlng[1]]);
            }
            addMap(latitude, longitude, places);
        } else {
            addMap(latitude, longitude);
        }
    }).catch(function (err) {
        console.log(err);
    });
}

function addMap(latitude, longitude, places) {
    //make places null if not supplied
    if (arguments.length == 2) {
        places = null;
    }
    var form = button.parentNode;
    var div = document.createElement('div');
    div.setAttribute('id', 'map');
    form.appendChild(div);
    L.mapbox.accessToken = 'pk.eyJ1Ijoiam9ubnliYXJuZXMiLCJhIjoiVlpndW1EYyJ9.aP9fxAqLKh7lj0LpFh5k1w';
    var map = L.mapbox.map('map', 'jonnybarnes.gnoihnim')
        .setView([latitude, longitude], 15)
        .addLayer(L.mapbox.tileLayer('jonnybarnes.gnoihnim', {
            detectRetina: true,
        }));
    var marker = L.marker([latitude, longitude], {
        draggable: true,
    }).addTo(map);
    if (places !== null) {
        places.forEach(function (item, index, array) {
            var placeMarker = L.marker([item[1], item[2]], {
                icon: L.mapbox.marker.icon({
                    'marker-size': 'large',
                    'marker-symbol': 'building',
                    'marker-color': '#fa0'
                })
            }).addTo(map);
            var name = 'Name: ' + item[0];
            placeMarker.bindPopup(name, {
                closeButton: true
            });
        });
    }
}

function parseLocation(point) {
    var re = /\((.*)\)/;
    var resultArray = re.exec(point);
    var location = resultArray[1].split(' ');

    return [location[1], location[0]];
}
